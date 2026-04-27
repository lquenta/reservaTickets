<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    public const EVENT_VIEW_EVENT = 'view_event';
    public const EVENT_BEGIN_RESERVATION = 'begin_reservation';
    public const EVENT_BEGIN_CHECKOUT = 'begin_checkout';
    public const EVENT_PURCHASE = 'purchase';

    protected $fillable = [
        'event_name',
        'session_id',
        'user_id',
        'event_id',
        'ip_address',
        'path',
        'referrer',
        'device_type',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
