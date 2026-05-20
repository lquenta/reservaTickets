<?php

namespace App\Http\Concerns;

use App\DTOs\AdminSaleContext;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Http\Request;

trait CreatesAdminSaleReservation
{
    protected function createReservationFromRequest(
        Request $request,
        User $client,
        Event $event,
        AdminSaleContext $adminSale,
        ReservationService $service
    ): Reservation {
        $singleName = $request->boolean('single_name', true);

        if ($event->venue_id) {
            $seatIds = array_map('intval', $request->input('seat_ids', []));
            if ($event->hasSections()) {
                $sectionQuantities = $request->input('section_quantities', []);

                return $service->createReservationWithSections(
                    $client,
                    $event,
                    $seatIds,
                    is_array($sectionQuantities) ? $sectionQuantities : [],
                    $request->all(),
                    $singleName,
                    $adminSale
                );
            }

            $count = count($seatIds);
            $names = $singleName
                ? array_fill(0, $count, $request->input('holder_name', $client->name))
                : array_map(fn ($i) => $request->input("holder_name_{$i}", ''), range(1, max(1, $count)));
            $seatAssignments = null;
            if (! $singleName && $count > 0) {
                $seatAssignments = array_map(fn ($i) => (int) $request->input("seat_for_{$i}"), range(1, $count));
            }

            return $service->createReservation($client, $event, $seatIds, $singleName, $names, $seatAssignments, $adminSale);
        }

        $quantity = (int) $request->input('quantity', 1);
        $names = $singleName
            ? [$request->input('holder_name', $client->name)]
            : array_map(fn ($i) => $request->input("holder_name_{$i}", ''), range(1, $quantity));

        return $service->createReservationWithoutSeats($client, $event, $quantity, $singleName, $names, $adminSale);
    }
}
