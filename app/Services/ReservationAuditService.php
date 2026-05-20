<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class ReservationAuditService
{
    public function log(
        string $action,
        string $result,
        ?User $performedBy = null,
        ?Event $event = null,
        ?Reservation $reservation = null,
        ?User $subjectUser = null,
        ?string $message = null
    ): ReservationAuditLog {
        $eventId = $event?->id ?? $reservation?->event_id;
        $performedById = $performedBy?->id ?? auth()->id();
        $subjectUserId = $subjectUser?->id ?? $reservation?->user_id;

        return ReservationAuditLog::create([
            'user_id' => $subjectUserId,
            'performed_by_user_id' => $performedById,
            'event_id' => $eventId,
            'reservation_id' => $reservation?->id,
            'action' => $action,
            'result' => $result,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'message' => $message,
        ]);
    }
}
