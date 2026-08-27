<?php

namespace App\Models;

use App\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const PROVISIONED_VIA_SURROGATE = 'admin_surrogate';

    public const PROVISIONED_VIA_HONORED_GUEST = 'admin_honored_guest';

    public const PROVISIONED_VIA_PUBLIC_GUEST = 'public_guest';

    protected $fillable = [
        'name',
        'email',
        'ci',
        'phone',
        'password',
        'role',
        'created_by_user_id',
        'provisioned_via',
        'is_guest',
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
            'is_guest' => 'boolean',
        ];
    }

    public function isGuest(): bool
    {
        return (bool) $this->is_guest;
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Correo al que se envían tickets. Para invitados de vendedor (@guest.local) es null.
     * Para invitados públicos se quita el sufijo único +pub-{ulid}.
     */
    public function notifyEmail(): ?string
    {
        $email = $this->email;
        if (! is_string($email) || $email === '') {
            return null;
        }

        if (str_ends_with(strtolower($email), '@guest.local')) {
            return null;
        }

        $stripped = preg_replace('/\+pub-[^@]+@/i', '@', $email);

        return $stripped ?: $email;
    }

    public function displayEmail(): ?string
    {
        if ($this->isGuest()) {
            return $this->notifyEmail();
        }

        return $this->email;
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
