<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;

class ReservationPricingService
{
    public function totalForReservation(Reservation $reservation): float
    {
        if ($reservation->isHonoredGuest()) {
            return 0.0;
        }

        if ($reservation->sale_amount !== null) {
            return (float) $reservation->sale_amount;
        }

        $reservation->loadMissing([
            'event.sections',
            'event.ticketTemplate',
            'reservationTickets.seat',
        ]);

        $event = $reservation->event;
        if (! $event) {
            return 0.0;
        }

        $tickets = $reservation->reservationTickets;
        if ($tickets->isEmpty()) {
            return 0.0;
        }

        if ($event->hasSections()) {
            return $this->totalForSectionedEvent($event, $tickets);
        }

        $unitPrice = $event->ticketTemplate ? (float) $event->ticketTemplate->price : 0.0;

        return $unitPrice * $tickets->count();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ReservationTicket>  $tickets
     */
    private function totalForSectionedEvent(Event $event, $tickets): float
    {
        $total = 0.0;

        foreach ($tickets as $ticket) {
            $eventSection = $this->resolveEventSection($event, $ticket);
            if ($eventSection && $eventSection->pivot && $eventSection->pivot->price !== null) {
                $total += (float) $eventSection->pivot->price;
            }
        }

        return $total;
    }

    private function resolveEventSection(Event $event, ReservationTicket $ticket): ?\App\Models\Section
    {
        if ($ticket->seat) {
            $seat = $ticket->seat;
            if ($seat->section_id) {
                $found = $event->sections->firstWhere('id', $seat->section_id);
                if ($found) {
                    return $found;
                }
            }
            foreach ($event->sections as $es) {
                if (! $es->has_seats) {
                    continue;
                }
                if ($es->containsSeat((int) $seat->row, (int) $seat->number)) {
                    return $es;
                }
            }

            return $event->sections->where('has_seats', true)->first();
        }

        if ($ticket->section_id) {
            return $event->sections->firstWhere('id', $ticket->section_id);
        }

        return null;
    }

    public function snapshotSaleAmount(Reservation $reservation): void
    {
        if ($reservation->status !== Reservation::STATUS_CONFIRMADO) {
            return;
        }

        $amount = $this->totalForReservation($reservation);
        if ($reservation->sale_amount === null || (float) $reservation->sale_amount !== $amount) {
            $reservation->update(['sale_amount' => $amount]);
        }
    }
}
