<?php

namespace App\Support;

use App\Models\Reservation;

class ReservationCheckoutMapData
{
    /**
     * Read-only seat map for checkout (highlights seats on the reservation).
     *
     * @return array<string, mixed>|null
     */
    public static function forReservation(Reservation $reservation): ?array
    {
        $reservation->loadMissing(['event', 'reservationTickets']);

        $event = $reservation->event;
        if (! $event || ! $event->venue_id) {
            return null;
        }

        $selection = EventSeatSelectionData::build($event);
        $layoutElements = $selection['layoutElements'] ?? [];
        if (! is_array($layoutElements) || $layoutElements === []) {
            return null;
        }

        $selectedSeatIds = $reservation->reservationTickets
            ->pluck('seat_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($selectedSeatIds === []) {
            return null;
        }

        return [
            'layoutElements' => $layoutElements,
            'layoutCanvas' => $selection['layoutCanvas'] ?? ['width' => null, 'height' => null],
            'sectionPalettesById' => $selection['sectionPalettesById'] ?? [],
            'selectedSeatIds' => $selectedSeatIds,
        ];
    }
}
