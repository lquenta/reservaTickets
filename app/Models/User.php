<?php

namespace App\Models;

use App\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const PROVISIONED_VIA_SURROGATE = 'admin_surrogate';

    public const PROVISIONED_VIA_HONORED_GUEST = 'admin_honored_guest';

    protected $fillable = [
        'name',
        'email',
        'ci',
        'phone',
        'password',
        'role',
        'created_by_user_id',
        'provisioned_via',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reservationsSold(): HasMany
    {
        return $this->hasMany(Reservation::class, 'sold_by_user_id');
    }

    public function reservationAuditLogs(): HasMany
    {
        return $this->hasMany(ReservationAuditLog::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_VENDEDOR = 'vendedor';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isVendedor(): bool
    {
        return $this->role === self::ROLE_VENDEDOR;
    }

    public function canSellOnBehalf(): bool
    {
        return $this->isAdmin() || $this->isVendedor();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
