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
        ?User $user = null,
        ?Event $event = null,
        ?Reservation $reservation = null,
        ?string $message = null
    ): ReservationAuditLog {
        $eventId = $event?->id ?? $reservation?->event_id;
        $userId = $user?->id ?? $reservation?->user_id;

        return ReservationAuditLog::create([
            'user_id' => $userId,
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
