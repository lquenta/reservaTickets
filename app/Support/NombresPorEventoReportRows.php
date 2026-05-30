<?php

namespace App\Support;

use App\Models\Reservation;
use App\Models\ReservationTicket;
use Illuminate\Support\Collection;

class NombresPorEventoReportRows
{
    /**
     * Filas del reporte ordenadas por butaca (fila A→Z, luego número ascendente).
     *
     * @param  Collection<int, Reservation>  $reservations
     * @return Collection<int, object{
     *     reservation: string,
     *     holder_name: string,
     *     seat_label: string,
     *     reserved_at: \Illuminate\Support\Carbon|null
     * }>
     */
    public static function fromReservations(Collection $reservations): Collection
    {
        $rows = collect();

        foreach ($reservations as $reservation) {
            $reservedAt = $reservation->confirmed_payment_at ?? $reservation->created_at;

            foreach ($reservation->reservationTickets as $ticket) {
                $rows->push(self::rowFromTicket($reservation, $ticket, $reservedAt));
            }
        }

        return $rows->sort(self::compare(...))->values();
    }

    private static function rowFromTicket(
        Reservation $reservation,
        ReservationTicket $ticket,
        ?\Illuminate\Support\Carbon $reservedAt,
    ): object {
        $holderName = $ticket->holder_name ?: '—';
        if ($reservation->sale_type === Reservation::SALE_TYPE_HONORED_GUEST) {
            $holderName .= ' (Invitado de Honor)';
        }

        $seat = $ticket->seat;

        return (object) [
            'reservation' => $reservation->payment_code ?? ('#'.$reservation->id),
            'holder_name' => $holderName,
            'seat_label' => $seat?->display_label ?? 'Sin butaca',
            'seat_row' => $seat ? (int) $seat->row : PHP_INT_MAX,
            'seat_number' => $seat ? (int) $seat->number : PHP_INT_MAX,
            'has_seat' => $seat !== null,
            'reserved_at' => $reservedAt,
        ];
    }

    private static function compare(object $a, object $b): int
    {
        if ($a->has_seat !== $b->has_seat) {
            return $a->has_seat ? -1 : 1;
        }

        if (! $a->has_seat) {
            return strcmp($a->holder_name, $b->holder_name);
        }

        $byRow = $a->seat_row <=> $b->seat_row;
        if ($byRow !== 0) {
            return $byRow;
        }

        return $a->seat_number <=> $b->seat_number;
    }
}
