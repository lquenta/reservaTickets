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
    public const ACTION_USER_PROVISIONED_BY_ADMIN = 'user_provisioned_by_admin';
    public const ACTION_SURROGATE_SALE_EXISTING_USER = 'surrogate_sale_existing_user';
    public const ACTION_SURROGATE_SALE_CREATED = 'surrogate_sale_created';
    public const ACTION_SURROGATE_CHECKOUT_CONFIRMED = 'surrogate_checkout_confirmed';
    public const ACTION_SURROGATE_DELIVERY_RESPONSIBILITY_ACCEPTED = 'surrogate_delivery_responsibility_accepted';
    public const ACTION_HONORED_GUEST_CREATED = 'honored_guest_created';
    public const ACTION_REFUNDED = 'refunded';
    public const ACTION_EVENT_RESCHEDULED = 'event_rescheduled';

    public const RESULT_SUCCESS = 'success';
    public const RESULT_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'performed_by_user_id',
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

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
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
