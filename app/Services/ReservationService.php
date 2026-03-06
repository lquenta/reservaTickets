<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Models\ReservationTicket;
use App\Models\Section;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ReservationService
{
    public const MAX_SEATS = 12;

    /** Máximo de reservas pendientes (INICIADO o PENDIENTE_PAGO) por usuario y evento. */
    public const MAX_PENDING_RESERVATIONS_PER_USER_EVENT = 1;

    /**
     * Reserva por butacas (evento con venue). Valida y crea en transacción.
     * Cuando $seatAssignments está presente (un nombre por ticket), cada posición i usa seatAssignments[i] y names[i].
     */
    public function createReservation(User $user, Event $event, array $seatIds, bool $singleName, array $names, ?array $seatAssignments = null): Reservation
    {
        $this->ensureUserCanReserveForEvent($user, $event);
        $this->validateSeatsForEvent($event, $seatIds);

        $prefix = $event->payment_code_prefix ?? 'EV';
        $code = strtoupper($prefix) . '-' . strtoupper(Str::random(6)) . '-' . Str::random(4);

        return DB::transaction(function () use ($user, $event, $seatIds, $singleName, $names, $seatAssignments, $code) {
            $this->validateSeatsForEvent($event, $seatIds);

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'status' => Reservation::STATUS_INICIADO,
                'payment_code' => $code,
                'expires_at' => now()->addMinutes(10),
            ]);

            $holderName = $singleName ? ($names[0] ?? '') : null;
            $order = $seatAssignments !== null ? $seatAssignments : $seatIds;
            foreach ($order as $i => $seatId) {
                ReservationTicket::create([
                    'reservation_id' => $reservation->id,
                    'seat_id' => $seatId,
                    'holder_name' => $singleName ? $holderName : ($names[$i] ?? ''),
                    'position' => $i + 1,
                ]);
            }

            return $reservation->fresh(['reservationTickets.seat', 'event']);
        });
    }

    /**
     * Reserva sin butacas (evento sin venue, legacy).
     */
    public function createReservationWithoutSeats(User $user, Event $event, int $quantity, bool $singleName, array $names): Reservation
    {
        $this->ensureUserCanReserveForEvent($user, $event);

        $prefix = $event->payment_code_prefix ?? 'EV';
        $code = strtoupper($prefix) . '-' . strtoupper(Str::random(6)) . '-' . Str::random(4);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_INICIADO,
            'payment_code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        $holderName = $singleName ? ($names[0] ?? '') : null;
        for ($i = 0; $i < $quantity; $i++) {
            ReservationTicket::create([
                'reservation_id' => $reservation->id,
                'holder_name' => $singleName ? $holderName : ($names[$i] ?? ''),
                'position' => $i + 1,
            ]);
        }

        return $reservation->fresh(['reservationTickets', 'event']);
    }

    /**
     * Reserva con secciones: butacas (opcional) + cantidades por sección sin butacas (opcional).
     * $sectionQuantities = [ section_id => quantity ].
     */
    public function createReservationWithSections(User $user, Event $event, array $seatIds, array $sectionQuantities, array $requestData, bool $singleName): Reservation
    {
        $this->ensureUserCanReserveForEvent($user, $event);

        $sectionQuantities = array_filter(array_map('intval', $sectionQuantities));
        $seatCount = count($seatIds);
        $sectionTotal = array_sum($sectionQuantities);
        $totalTickets = $seatCount + $sectionTotal;

        if ($totalTickets < 1 || $totalTickets > self::MAX_SEATS) {
            throw ValidationException::withMessages([
                'seat_ids' => ['El total de entradas debe ser entre 1 y ' . self::MAX_SEATS . '.'],
            ]);
        }

        $event->load('sections');
        $sectionIdsWithSeats = $event->sections->where('has_seats', true)->pluck('id')->flip();
        $sectionIdsWithoutSeats = $event->sections->where('has_seats', false)->pluck('id')->flip();

        if ($seatCount > 0) {
            $this->validateSeatsForEventSections($event, $seatIds, $sectionIdsWithSeats);
        }
        foreach ($sectionQuantities as $sectionId => $qty) {
            if ($qty < 1) {
                continue;
            }
            if (! $sectionIdsWithoutSeats->has($sectionId)) {
                throw ValidationException::withMessages(['section_quantities' => ['Sección no válida.']]);
            }
            $section = Section::find($sectionId);
            if (! $section || $section->venue_id != $event->venue_id) {
                throw ValidationException::withMessages(['section_quantities' => ['Sección no válida.']]);
            }
            $available = $event->availableCapacityForSection($section);
            if ($qty > $available) {
                throw ValidationException::withMessages(['section_quantities' => ["No hay suficientes entradas disponibles en {$section->name}."]]);
            }
        }

        $names = $this->collectNamesForTotal($totalTickets, $singleName, $requestData);

        $prefix = $event->payment_code_prefix ?? 'EV';
        $code = strtoupper($prefix) . '-' . strtoupper(Str::random(6)) . '-' . Str::random(4);

        return DB::transaction(function () use ($user, $event, $seatIds, $sectionQuantities, $names, $singleName, $code) {
            $reservation = Reservation::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'status' => Reservation::STATUS_INICIADO,
                'payment_code' => $code,
                'expires_at' => now()->addMinutes(10),
            ]);

            $holderName = $singleName ? ($names[0] ?? '') : null;
            $position = 1;
            $nameIndex = 0;
            foreach ($seatIds as $seatId) {
                ReservationTicket::create([
                    'reservation_id' => $reservation->id,
                    'seat_id' => (int) $seatId,
                    'holder_name' => $singleName ? $holderName : ($names[$nameIndex++] ?? ''),
                    'position' => $position++,
                ]);
            }
            foreach ($sectionQuantities as $sectionId => $qty) {
                if ($qty < 1) {
                    continue;
                }
                for ($k = 0; $k < $qty; $k++) {
                    ReservationTicket::create([
                        'reservation_id' => $reservation->id,
                        'section_id' => (int) $sectionId,
                        'holder_name' => $singleName ? $holderName : ($names[$nameIndex++] ?? ''),
                        'position' => $position++,
                    ]);
                }
            }

            return $reservation->fresh(['reservationTickets.seat', 'reservationTickets.section', 'event']);
        });
    }

    private function collectNamesForTotal(int $total, bool $singleName, array $requestData): array
    {
        if ($singleName) {
            return array_fill(0, $total, $requestData['holder_name'] ?? '');
        }
        $names = [];
        for ($i = 1; $i <= $total; $i++) {
            $names[] = $requestData["holder_name_{$i}"] ?? '';
        }
        return $names;
    }

    private function validateSeatsForEventSections(Event $event, array $seatIds, $sectionIdsWithSeats): void
    {
        if (empty($seatIds)) {
            return;
        }
        $seats = Seat::whereIn('id', $seatIds)->where('venue_id', $event->venue_id)->get();
        if ($seats->count() !== count($seatIds)) {
            throw ValidationException::withMessages(['seat_ids' => ['Algunas butacas no pertenecen al lugar de este evento.']]);
        }
        foreach ($seats as $seat) {
            if ($seat->blocked) {
                throw ValidationException::withMessages(['seat_ids' => ['Algunas butacas están bloqueadas.']]);
            }
            if ($event->hasSections() && $seat->section_id && ! $sectionIdsWithSeats->has($seat->section_id)) {
                throw ValidationException::withMessages(['seat_ids' => ['Las butacas deben pertenecer a una sección activa del evento.']]);
            }
        }
        $availableIds = $event->hasSections()
            ? $event->sections->where('has_seats', true)->flatMap(function ($s) use ($event) {
                $ids = $event->availableSeats($s->id)->pluck('id');
                if ($ids->isEmpty() && $s->row_start !== null && $s->row_end !== null) {
                    $ids = $event->availableSeats(null)->whereBetween('row', [$s->row_start, $s->row_end])->pluck('id');
                }
                if ($ids->isEmpty()) {
                    $ids = $event->availableSeats(null)->pluck('id');
                }
                return $ids;
            })->flip()
            : $event->availableSeats()->pluck('id')->flip();
        foreach ($seatIds as $id) {
            if (! $availableIds->has((int) $id)) {
                throw ValidationException::withMessages(['seat_ids' => ['Una o más butacas ya no están disponibles.']]);
            }
        }
    }

    private function ensureUserCanReserveForEvent(User $user, Event $event): void
    {
        $count = Reservation::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->whereIn('status', [Reservation::STATUS_INICIADO, Reservation::STATUS_PENDIENTE_PAGO])
            ->count();

        if ($count >= self::MAX_PENDING_RESERVATIONS_PER_USER_EVENT) {
            $message = 'Ya tienes una reserva pendiente para este evento. Completa el pago o espera a que expire antes de reservar de nuevo.';
            app(ReservationAuditService::class)->log(
                ReservationAuditLog::ACTION_RESERVATION_ATTEMPT,
                ReservationAuditLog::RESULT_FAILED,
                $user,
                $event,
                null,
                $message
            );
            throw ValidationException::withMessages([
                'event_id' => [$message],
            ]);
        }
    }

    private function validateSeatsForEvent(Event $event, array $seatIds): void
    {
        if (! $event->venue_id) {
            throw ValidationException::withMessages(['seat_ids' => ['Este evento no tiene reserva por butacas.']]);
        }
        if (count($seatIds) < 1 || count($seatIds) > self::MAX_SEATS) {
            throw ValidationException::withMessages(['seat_ids' => ['Debes elegir entre 1 y ' . self::MAX_SEATS . ' butacas.']]);
        }

        $seats = Seat::whereIn('id', $seatIds)->where('venue_id', $event->venue_id)->get();
        if ($seats->count() !== count($seatIds)) {
            throw ValidationException::withMessages(['seat_ids' => ['Algunas butacas no pertenecen al lugar de este evento.']]);
        }

        $blockedOrTaken = $seats->filter(function ($seat) {
            return $seat->blocked;
        });
        if ($blockedOrTaken->isNotEmpty()) {
            throw ValidationException::withMessages(['seat_ids' => ['Algunas butacas están bloqueadas.']]);
        }

        $availableIds = $event->availableSeats()->pluck('id')->flip();
        foreach ($seatIds as $id) {
            if (! $availableIds->has($id)) {
                throw ValidationException::withMessages(['seat_ids' => ['Una o más butacas ya no están disponibles. Vuelve a intentar.']]);
            }
        }
    }
}
