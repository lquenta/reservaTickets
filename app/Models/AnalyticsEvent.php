<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    public const EVENT_VIEW_EVENT = 'view_event';

    public const EVENT_BEGIN_RESERVATION = 'begin_reservation';

    public const EVENT_BEGIN_CHECKOUT = 'begin_checkout';

    public const EVENT_PURCHASE = 'purchase';

    public const EVENT_PAGE_VIEW = 'page_view';

    public const EVENT_PAGE_DURATION = 'page_duration';

    public const EVENT_CLICK = 'click';

    public const EVENT_SUBMIT = 'submit';

    /** @var list<string> */
    public const CLIENT_NAMES = [
        self::EVENT_PAGE_VIEW,
        self::EVENT_PAGE_DURATION,
        self::EVENT_CLICK,
        self::EVENT_SUBMIT,
        self::EVENT_VIEW_EVENT,
        self::EVENT_BEGIN_RESERVATION,
        self::EVENT_BEGIN_CHECKOUT,
    ];

    /** @var list<string> */
    public const ACTION_NAMES = [
        self::EVENT_CLICK,
        self::EVENT_SUBMIT,
        self::EVENT_VIEW_EVENT,
        self::EVENT_BEGIN_RESERVATION,
        self::EVENT_BEGIN_CHECKOUT,
        self::EVENT_PURCHASE,
    ];

    protected $fillable = [
        'event_name',
        'session_id',
        'user_id',
        'event_id',
        'ip_address',
        'path',
        'referrer',
        'label',
        'route_name',
        'duration_ms',
        'view_token',
        'device_type',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'duration_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trackedEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function actionLabel(): string
    {
        return match ($this->event_name) {
            self::EVENT_PAGE_VIEW => 'Vio la página',
            self::EVENT_CLICK => 'Clic',
            self::EVENT_SUBMIT => 'Envió un formulario',
            self::EVENT_VIEW_EVENT => 'Vio un evento',
            self::EVENT_BEGIN_RESERVATION => 'Empezó una reserva',
            self::EVENT_BEGIN_CHECKOUT => 'Empezó el pago',
            self::EVENT_PURCHASE => 'Compra',
            default => $this->event_name,
        };
    }

    public function deviceLabel(): string
    {
        return match ($this->device_type) {
            'mobile' => 'Móvil',
            'tablet' => 'Tablet',
            'server' => 'Sistema',
            default => 'Escritorio',
        };
    }
}
