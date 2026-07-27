<?php

namespace App\Support;

use App\Models\Reservation;
use App\Services\ReservationPricingService;
use Illuminate\Support\Collection;

class SalesByEventTotals
{
    /**
     * Agrega montos cobrados por evento.
     *
     * Usa sale_amount (snapshot al confirmar, incluye preventa). Si falta, recalcula
     * con ReservationPricingService. Omite invitados de honor y tickets reembolsados.
     *
     * @param  Collection<int, Reservation>  $reservations
     * @return Collection<int, object{
     *     event_id: int,
     *     event_name: string,
     *     tickets_sold: int,
     *     unit_price: float,
     *     total: float
     * }>
     */
    public static function fromReservations(Collection $reservations, ?ReservationPricingService $pricing = null): Collection
    {
        $pricing ??= app(ReservationPricingService::class);
        $byEvent = [];

        foreach ($reservations as $reservation) {
            if ($reservation->sale_type === Reservation::SALE_TYPE_HONORED_GUEST) {
                continue;
            }

            $tickets = self::activeTickets($reservation);
            $ticketCount = $tickets->count();
            if ($ticketCount === 0) {
                continue;
            }

            $amount = $reservation->sale_amount !== null
                ? (float) $reservation->sale_amount
                : $pricing->totalForReservation($reservation);

            $eventId = (int) $reservation->event_id;
            if (! isset($byEvent[$eventId])) {
                $byEvent[$eventId] = [
                    'event_id' => $eventId,
                    'event_name' => $reservation->event?->name ?? ('Evento #'.$eventId),
                    'tickets_sold' => 0,
                    'total' => 0.0,
                ];
            }

            $byEvent[$eventId]['tickets_sold'] += $ticketCount;
            $byEvent[$eventId]['total'] += $amount;
        }

        return collect($byEvent)
            ->map(fn (array $row) => self::toRow($row))
            ->values();
    }

    /**
     * @param  array{event_id: int, event_name: string, tickets_sold: int, total: float}  $row
     */
    public static function toRow(array $row): object
    {
        $ticketsSold = (int) $row['tickets_sold'];
        $total = round((float) $row['total'], 2);

        return (object) [
            'event_id' => (int) $row['event_id'],
            'event_name' => (string) $row['event_name'],
            'tickets_sold' => $ticketsSold,
            'unit_price' => $ticketsSold > 0 ? round($total / $ticketsSold, 2) : 0.0,
            'total' => $total,
        ];
    }

    /**
     * @return Collection<int, \App\Models\ReservationTicket>
     */
    private static function activeTickets(Reservation $reservation): Collection
    {
        $tickets = $reservation->relationLoaded('reservationTickets')
            ? $reservation->reservationTickets
            : $reservation->reservationTickets()->get();

        return $tickets->filter(fn ($ticket) => ! $ticket->isRefunded())->values();
    }
}
