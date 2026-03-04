<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Models\ReservationTicket;
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
