<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Models\ReservationTicket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundService
{
    public function __construct(
        private readonly ReservationPricingService $pricingService,
        private readonly ReservationAuditService $auditService,
    ) {}

    public function refund(Reservation $reservation, User $admin, ?string $reason = null): Reservation
    {
        $reservation->load('reservationTickets');

        $ticketIds = $reservation->reservationTickets
            ->filter(fn (ReservationTicket $t) => $t->isRefundable())
            ->pluck('id')
            ->all();

        if ($ticketIds === []) {
            throw new InvalidArgumentException('No hay entradas reembolsables en esta reserva.');
        }

        return $this->refundTickets($reservation, $admin, $ticketIds, $reason);
    }

    /**
     * @param  list<int>  $ticketIds
     */
    public function refundTickets(Reservation $reservation, User $admin, array $ticketIds, ?string $reason = null): Reservation
    {
        if ($reservation->status !== Reservation::STATUS_CONFIRMADO) {
            throw new InvalidArgumentException('Solo se pueden reembolsar reservas confirmadas.');
        }

        $ticketIds = array_values(array_unique(array_map('intval', $ticketIds)));
        if ($ticketIds === []) {
            throw new InvalidArgumentException('Debes seleccionar al menos una entrada para reembolsar.');
        }

        $reservation->loadMissing(['event', 'reservationTickets.seat', 'reservationTickets.section']);

        $tickets = $reservation->reservationTickets
            ->whereIn('id', $ticketIds)
            ->values();

        if ($tickets->count() !== count($ticketIds)) {
            throw new InvalidArgumentException('Una o más entradas no pertenecen a esta reserva.');
        }

        foreach ($tickets as $ticket) {
            if (! $ticket->isRefundable()) {
                throw new InvalidArgumentException('No se pueden reembolsar entradas ya validadas en puerta o ya reembolsadas.');
            }
        }

        $activeTickets = $reservation->reservationTickets->filter(fn (ReservationTicket $t) => ! $t->isRefunded());
        $refundingAllActive = $tickets->count() === $activeTickets->count();
        $amount = $this->pricingService->totalForTickets($reservation, $tickets);

        return DB::transaction(function () use ($reservation, $admin, $reason, $amount, $tickets, $refundingAllActive) {
            $now = now();
            ReservationTicket::query()
                ->whereIn('id', $tickets->pluck('id'))
                ->update(['refunded_at' => $now]);

            if ($refundingAllActive) {
                $totalRefunded = (float) ($reservation->refund_amount ?? 0) + $amount;

                $reservation->update([
                    'status' => Reservation::STATUS_REEMBOLSADO,
                    'refunded_at' => $now,
                    'refunded_by_user_id' => $admin->id,
                    'refund_reason' => $reason,
                    'refund_amount' => $totalRefunded,
                    'sale_amount' => 0,
                ]);

                $this->auditService->log(
                    ReservationAuditLog::ACTION_REFUNDED,
                    ReservationAuditLog::RESULT_SUCCESS,
                    $admin,
                    $reservation->event,
                    $reservation,
                    $reservation->user,
                    'Reembolso manual por admin.'.($reason ? ' Motivo: '.$reason : '')
                );
            } else {
                $remainingTotal = $this->pricingService->totalForActiveTickets($reservation->fresh(['reservationTickets.seat', 'reservationTickets.section']));
                $totalRefunded = (float) ($reservation->refund_amount ?? 0) + $amount;

                $reservation->update([
                    'sale_amount' => $remainingTotal,
                    'refund_amount' => $totalRefunded,
                ]);

                $labels = $this->ticketLabels($tickets);
                $this->auditService->log(
                    ReservationAuditLog::ACTION_PARTIALLY_REFUNDED,
                    ReservationAuditLog::RESULT_SUCCESS,
                    $admin,
                    $reservation->event,
                    $reservation,
                    $reservation->user,
                    'Reembolso parcial: '.implode(', ', $labels).'. Monto: '.number_format($amount, 2).' Bs.'
                        .($reason ? ' Motivo: '.$reason : '')
                );
            }

            return $reservation->fresh();
        });
    }

    /**
     * Revierte un reembolso erróneo: las entradas vuelven a estar activas (mismo QR).
     *
     * @param  list<int>  $ticketIds
     */
    public function restoreRefundedTickets(Reservation $reservation, array $ticketIds, bool $dryRun = false): Reservation
    {
        $ticketIds = array_values(array_unique(array_map('intval', $ticketIds)));
        if ($ticketIds === []) {
            throw new InvalidArgumentException('Indica al menos un ID de entrada (reservation_tickets.id).');
        }

        $reservation->loadMissing(['event', 'reservationTickets.seat', 'reservationTickets.section']);

        $tickets = $reservation->reservationTickets
            ->whereIn('id', $ticketIds)
            ->values();

        if ($tickets->count() !== count($ticketIds)) {
            throw new InvalidArgumentException('Una o más entradas no pertenecen a esta reserva.');
        }

        foreach ($tickets as $ticket) {
            if (! $ticket->isRefunded()) {
                throw new InvalidArgumentException("La entrada #{$ticket->id} no está reembolsada.");
            }
            if ($ticket->seat_id && $reservation->event) {
                $seatFree = $reservation->event->availableSeats()->where('id', $ticket->seat_id)->exists();
                if (! $seatFree) {
                    $label = $ticket->seat?->display_label ?? (string) $ticket->seat_id;
                    throw new InvalidArgumentException("La butaca {$label} ya está ocupada por otra reserva.");
                }
            }
        }

        if ($dryRun) {
            return $reservation;
        }

        return DB::transaction(function () use ($reservation, $tickets, $ticketIds) {
            $revertedAmount = $this->pricingService->totalForTickets($reservation, $tickets);

            ReservationTicket::query()
                ->whereIn('id', $ticketIds)
                ->update(['refunded_at' => null]);

            $reservation->refresh()->load('reservationTickets.seat', 'reservationTickets.section');

            $updates = [
                'sale_amount' => $this->pricingService->totalForActiveTickets($reservation),
            ];

            $newRefundAmount = max(0, (float) ($reservation->refund_amount ?? 0) - $revertedAmount);
            $updates['refund_amount'] = $newRefundAmount > 0 ? $newRefundAmount : null;

            if ($reservation->status === Reservation::STATUS_REEMBOLSADO) {
                $updates['status'] = Reservation::STATUS_CONFIRMADO;
                $updates['refunded_at'] = null;
                $updates['refunded_by_user_id'] = null;
                $updates['refund_reason'] = null;
            }

            $reservation->update($updates);

            return $reservation->fresh();
        });
    }

    /**
     * @param  Collection<int, ReservationTicket>  $tickets
     * @return list<string>
     */
    private function ticketLabels(Collection $tickets): array
    {
        return $tickets->map(function (ReservationTicket $ticket) {
            $label = $ticket->holder_name;
            if ($ticket->seat) {
                $label .= ' ('.$ticket->seat->display_label.')';
            } elseif ($ticket->section) {
                $label .= ' ('.$ticket->section->name.')';
            }

            return $label;
        })->all();
    }
}
