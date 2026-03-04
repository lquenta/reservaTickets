<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationAuditLog extends Model
{
    public const ACTION_RESERVATION_ATTEMPT = 'reservation_attempt';
    public const ACTION_RESERVATION_CREATED = 'reservation_created';
    public const ACTION_CHECKOUT_CONFIRMED = 'checkout_confirmed';
    public const ACTION_AUTHORIZED = 'authorized';
    public const ACTION_REJECTED = 'rejected';

    public const RESULT_SUCCESS = 'success';
    public const RESULT_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'event_id',
        'reservation_id',
        'action',
        'result',
        'ip_address',
        'user_agent',
        'message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
