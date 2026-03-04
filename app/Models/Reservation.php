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

    protected $fillable = [
        'user_id',
        'event_id',
        'status',
        'payment_code',
        'expires_at',
        'confirmed_payment_at',
        'payment_receipt_path',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'confirmed_payment_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function reservationTickets(): HasMany
    {
        return $this->hasMany(ReservationTicket::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
