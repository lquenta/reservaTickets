<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use HasFactory;

    public const STATUS_INICIADO = 'INICIADO';

    public const STATUS_PENDIENTE_PAGO = 'PENDIENTE_PAGO';

    public const STATUS_CONFIRMADO = 'CONFIRMADO';

    public const STATUS_CANCELADO = 'CANCELADO';

    public const STATUS_REEMBOLSADO = 'REEMBOLSADO';

    public const SALE_TYPE_STANDARD = 'standard';

    public const SALE_TYPE_SURROGATE = 'surrogate';

    public const SALE_TYPE_HONORED_GUEST = 'honored_guest';

    protected $fillable = [
        'user_id',
        'sold_by_user_id',
        'sale_type',
        'event_id',
        'status',
        'payment_code',
        'expires_at',
        'confirmed_payment_at',
        'sale_amount',
        'refunded_at',
        'refunded_by_user_id',
        'refund_reason',
        'refund_amount',
        'payment_receipt_path',
        'seller_delivery_acknowledged_at',
        'seller_delivery_acknowledged_by_user_id',
        'tickets_emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'confirmed_payment_at' => 'datetime',
            'sale_amount' => 'decimal:2',
            'refunded_at' => 'datetime',
            'refund_amount' => 'decimal:2',
            'seller_delivery_acknowledged_at' => 'datetime',
            'tickets_emailed_at' => 'datetime',
        ];
    }

    public function hasTicketsEmailed(): bool
    {
        return $this->tickets_emailed_at !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by_user_id');
    }

    public function sellerDeliveryAcknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_delivery_acknowledged_by_user_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by_user_id');
    }

    public function reservationTickets(): HasMany
    {
        return $this->hasMany(ReservationTicket::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isSurrogateSale(): bool
    {
        return $this->sale_type === self::SALE_TYPE_SURROGATE;
    }

    public function isHonoredGuest(): bool
    {
        return $this->sale_type === self::SALE_TYPE_HONORED_GUEST;
    }

    public function isAdminSale(): bool
    {
        return $this->isSurrogateSale() || $this->isHonoredGuest();
    }

    public function hasValidatedTickets(): bool
    {
        if ($this->relationLoaded('reservationTickets')) {
            return $this->reservationTickets->contains(fn ($t) => $t->validated_at !== null);
        }

        return $this->reservationTickets()->whereNotNull('validated_at')->exists();
    }
}
