<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\Seat;
use App\Support\SeatLabelSearch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReservationTicketAssignmentService
{
    /**
     * @param  list<array{ticket: ReservationTicket, seat_label: ?string, holder_name: ?string}>  $updates
     */
    public function apply(Reservation $reservation, array $updates, bool $dryRun = false): Reservation
    {
        if ($updates === []) {
            throw new InvalidArgumentException('Indica al menos una actualización (id:butaca:nombre).');
        }

        $reservation->loadMissing(['event', 'reservationTickets.seat']);

        $event = $reservation->event;
        if (! $event?->venue_id) {
            throw new InvalidArgumentException('Este evento no usa butacas numeradas.');
        }

        $ticketsById = $reservation->reservationTickets->keyBy('id');
        $resolved = [];

        foreach ($updates as $update) {
            $ticket = $update['ticket'];
            if ($ticket->reservation_id !== $reservation->id) {
                throw new InvalidArgumentException("La entrada #{$ticket->id} no pertenece a esta reserva.");
            }
            if ($ticket->isRefunded()) {
                throw new InvalidArgumentException("La entrada #{$ticket->id} está reembolsada; restáurala antes de reasignar.");
            }

            $newName = $update['holder_name'];
            $newSeatId = $ticket->seat_id;

            if ($update['seat_label'] !== null && $update['seat_label'] !== '') {
                $newSeatId = $this->resolveSeatId($event, $update['seat_label']);
            }

            if ($newName === null && $newSeatId === $ticket->seat_id) {
                throw new InvalidArgumentException("La entrada #{$ticket->id} no tiene cambios.");
            }

            $resolved[$ticket->id] = [
                'ticket' => $ticket,
                'holder_name' => $newName ?? $ticket->holder_name,
                'seat_id' => $newSeatId,
            ];
        }

        $this->assertFinalSeatsAreAssignable($reservation, $event, $resolved);

        if ($dryRun) {
            return $reservation;
        }

        return DB::transaction(function () use ($resolved) {
            foreach ($resolved as $row) {
                /** @var ReservationTicket $ticket */
                $ticket = $row['ticket'];
                $payload = [];
                if ($row['holder_name'] !== $ticket->holder_name) {
                    $payload['holder_name'] = $row['holder_name'];
                }
                if ($row['seat_id'] !== $ticket->seat_id) {
                    $payload['seat_id'] = $row['seat_id'];
                }
                if ($payload !== []) {
                    $ticket->update($payload);
                }
            }

            return $ticket->reservation->fresh(['reservationTickets.seat']);
        });
    }

    private function resolveSeatId(Event $event, string $label): int
    {
        $query = Seat::query()->where('venue_id', $event->venue_id);
        SeatLabelSearch::applyToSeatQuery($query, $label);
        $seat = $query->first();

        if (! $seat) {
            throw new InvalidArgumentException("Butaca no encontrada en el venue: {$label}");
        }

        if ($seat->blocked || $event->blockedSeatIds()->contains($seat->id)) {
            throw new InvalidArgumentException("La butaca {$seat->display_label} está bloqueada.");
        }

        return $seat->id;
    }

    /**
     * @param  array<int, array{ticket: ReservationTicket, holder_name: string, seat_id: ?int}>  $resolved
     */
    private function assertFinalSeatsAreAssignable(Reservation $reservation, Event $event, array $resolved): void
    {
        /** @var Collection<int, ReservationTicket> $allTickets */
        $allTickets = $reservation->reservationTickets;

        $finalSeatByTicket = [];
        foreach ($allTickets as $ticket) {
            $finalSeatByTicket[$ticket->id] = array_key_exists($ticket->id, $resolved)
                ? $resolved[$ticket->id]['seat_id']
                : $ticket->seat_id;
        }

        $seatIds = array_values(array_filter($finalSeatByTicket));
        if (count($seatIds) !== count(array_unique($seatIds))) {
            throw new InvalidArgumentException('Dos entradas de la misma reserva no pueden usar la misma butaca.');
        }

        $seatsReleasedByThisBatch = [];
        foreach ($allTickets as $ticket) {
            $final = $finalSeatByTicket[$ticket->id];
            if ($ticket->seat_id && $ticket->seat_id !== $final) {
                $seatsReleasedByThisBatch[] = $ticket->seat_id;
            }
        }

        foreach ($resolved as $ticketId => $row) {
            $seatId = $finalSeatByTicket[$ticketId];
            if ($seatId === null) {
                continue;
            }

            if ($event->availableSeats()->where('id', $seatId)->exists()) {
                continue;
            }

            if (in_array($seatId, $seatsReleasedByThisBatch, true)) {
                continue;
            }

            if ($row['ticket']->seat_id === $seatId) {
                continue;
            }

            $seat = Seat::find($seatId);
            $label = $seat?->display_label ?? (string) $seatId;
            throw new InvalidArgumentException("La butaca {$label} ya está ocupada por otra reserva.");
        }
    }
}
