<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventReschedule;
use App\Models\ReservationAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EventRescheduleService
{
    public function __construct(private readonly ReservationAuditService $auditService) {}

    public function reschedule(Event $event, Carbon $newStartsAt, User $admin, ?string $reason = null): Event
    {
        return DB::transaction(function () use ($event, $newStartsAt, $admin, $reason) {
            $previous = $event->starts_at->copy();

            EventReschedule::create([
                'event_id' => $event->id,
                'previous_starts_at' => $previous,
                'new_starts_at' => $newStartsAt,
                'reason' => $reason,
                'performed_by_user_id' => $admin->id,
            ]);

            $event->update(['starts_at' => $newStartsAt]);

            $this->auditService->log(
                ReservationAuditLog::ACTION_EVENT_RESCHEDULED,
                ReservationAuditLog::RESULT_SUCCESS,
                $admin,
                $event,
                null,
                null,
                sprintf(
                    'Evento reprogramado de %s a %s.',
                    $previous->format('d/m/Y H:i'),
                    $newStartsAt->format('d/m/Y H:i')
                ).($reason ? ' Motivo: '.$reason : '')
            );

            return $event->fresh();
        });
    }
}
