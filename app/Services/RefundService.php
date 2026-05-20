<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Models\User;
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
        if ($reservation->status !== Reservation::STATUS_CONFIRMADO) {
            throw new InvalidArgumentException('Solo se pueden reembolsar reservas confirmadas.');
        }

        if ($reservation->hasValidatedTickets()) {
            throw new InvalidArgumentException('No se puede reembolsar: al menos una entrada ya fue validada en puerta.');
        }

        $amount = $reservation->sale_amount !== null
            ? (float) $reservation->sale_amount
            : $this->pricingService->totalForReservation($reservation);

        return DB::transaction(function () use ($reservation, $admin, $reason, $amount) {
            $reservation->update([
                'status' => Reservation::STATUS_REEMBOLSADO,
                'refunded_at' => now(),
                'refunded_by_user_id' => $admin->id,
                'refund_reason' => $reason,
                'refund_amount' => $amount,
            ]);

            $reservation->load('event');
            $this->auditService->log(
                ReservationAuditLog::ACTION_REFUNDED,
                ReservationAuditLog::RESULT_SUCCESS,
                $admin,
                $reservation->event,
                $reservation,
                $reservation->user,
                'Reembolso manual por admin.'.($reason ? ' Motivo: '.$reason : '')
            );

            return $reservation->fresh();
        });
    }
}
