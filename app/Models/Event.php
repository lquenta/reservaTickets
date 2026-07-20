<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'starts_at',
        'venue',
        'venue_id',
        'cover_image_path',
        'qr_image_path',
        'payment_code_prefix',
        'is_active',
        'sales_paused',
        'presale_enabled',
        'presale_discount_type',
        'presale_discount_value',
        'presale_starts_at',
        'presale_ends_at',
    ];

    public const PRESALE_TYPE_PERCENT = 'percent';

    public const PRESALE_TYPE_FIXED = 'fixed';

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'is_active' => 'boolean',
            'sales_paused' => 'boolean',
            'presale_enabled' => 'boolean',
            'presale_discount_value' => 'decimal:2',
            'presale_starts_at' => 'datetime',
            'presale_ends_at' => 'datetime',
        ];
    }

    public const SALES_CONTACT_PHONE = '64066996';

    public function acceptsReservations(): bool
    {
        return $this->is_active
            && ! $this->sales_paused
            && $this->starts_at->isFuture();
    }

    /**
     * Ventana de preventa activa (interruptor + fechas), sin mirar montos.
     */
    public function isPresaleWindowActive(?\Carbon\CarbonInterface $at = null): bool
    {
        if (! $this->presale_enabled) {
            return false;
        }

        if ($this->presale_starts_at === null || $this->presale_ends_at === null) {
            return false;
        }

        $at = $at ?? now();

        return $at->greaterThanOrEqualTo($this->presale_starts_at)
            && $at->lessThanOrEqualTo($this->presale_ends_at);
    }

    /**
     * Preventa vigente para mostrar badges: ventana activa y hay al menos un descuento configurable.
     * Con sectores: algún sector con tipo/valor; sin sectores: descuento a nivel evento.
     */
    public function isPresaleActive(?\Carbon\CarbonInterface $at = null): bool
    {
        if (! $this->isPresaleWindowActive($at)) {
            return false;
        }

        if ($this->relationLoaded('sections') ? $this->sections->isNotEmpty() : $this->hasSections()) {
            $this->loadMissing('sections');

            foreach ($this->sections as $section) {
                if ($this->presaleDiscountConfigIsValid(
                    $section->pivot->presale_discount_type ?? null,
                    $section->pivot->presale_discount_value ?? null
                )) {
                    return true;
                }
            }

            return false;
        }

        return $this->presaleDiscountConfigIsValid(
            $this->presale_discount_type,
            $this->presale_discount_value
        );
    }

    /**
     * Aplica preventa al precio unitario.
     * Si se pasa un Section del evento (con pivot), usa el descuento del sector.
     * Si no, usa el descuento a nivel evento (eventos sin sectores / plantilla).
     *
     * @param  \App\Models\Section|null  $eventSection
     */
    public function applyPresaleDiscount(float $unitPrice, $eventSection = null, ?\Carbon\CarbonInterface $at = null): float
    {
        $unitPrice = max(0.0, $unitPrice);

        if (! $this->isPresaleWindowActive($at)) {
            return round($unitPrice, 2);
        }

        if ($eventSection !== null) {
            $type = $eventSection->pivot->presale_discount_type ?? null;
            $value = $eventSection->pivot->presale_discount_value ?? null;
        } else {
            $type = $this->presale_discount_type;
            $value = $this->presale_discount_value;
        }

        if (! $this->presaleDiscountConfigIsValid($type, $value)) {
            return round($unitPrice, 2);
        }

        $value = (float) $value;

        if ($type === self::PRESALE_TYPE_PERCENT) {
            return max(0.0, round($unitPrice * (1 - ($value / 100)), 2));
        }

        return max(0.0, round($unitPrice - $value, 2));
    }

    public function presaleDiscountConfigIsValid(mixed $type, mixed $value): bool
    {
        if (! in_array($type, [self::PRESALE_TYPE_PERCENT, self::PRESALE_TYPE_FIXED], true)) {
            return false;
        }

        if ($value === null || $value === '') {
            return false;
        }

        $num = (float) $value;
        if ($num < 0) {
            return false;
        }

        if ($type === self::PRESALE_TYPE_PERCENT && $num > 100) {
            return false;
        }

        return true;
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** Secciones del evento (plano): solo las que el admin activó para este evento, con precio. */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'event_section')
            ->withPivot(['price', 'sort_order', 'presale_discount_type', 'presale_discount_value'])
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }

    /** True si el evento usa división por sectores (tiene al menos una sección asignada). */
    public function hasSections(): bool
    {
        return $this->sections()->exists();
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reschedules(): HasMany
    {
        return $this->hasMany(EventReschedule::class)->latest();
    }

    public function blockedSeats(): BelongsToMany
    {
        return $this->belongsToMany(Seat::class, 'event_seat_blocks')
            ->withTimestamps();
    }

    /**
     * Butacas disponibles para este evento: del venue del evento, no bloqueadas,
     * y sin reserva activa (INICIADO no expirada, PENDIENTE_PAGO o CONFIRMADO) de este evento.
     * Si el evento usa secciones, opcionalmente filtra por $sectionId.
     */
    public function availableSeats(?int $sectionId = null): Builder
    {
        if (! $this->venue_id) {
            return Seat::query()->whereRaw('1 = 0');
        }

        $query = Seat::query()
            ->where('venue_id', $this->venue_id)
            ->where('blocked', false)
            ->whereDoesntHave('eventsBlocked', function ($q) {
                $q->where('events.id', $this->id);
            })
            ->whereDoesntHave('reservationTickets', function ($q) {
                $q->active()->whereHas('reservation', function ($q2) {
                    $q2->where('event_id', $this->id)
                        ->where(function ($q3) {
                            $q3->whereIn('status', [
                                Reservation::STATUS_PENDIENTE_PAGO,
                                Reservation::STATUS_CONFIRMADO,
                            ])->orWhere(function ($q4) {
                                $q4->where('status', Reservation::STATUS_INICIADO)
                                    ->where(function ($q5) {
                                        $q5->whereNull('reservations.expires_at')
                                            ->orWhere('reservations.expires_at', '>', now());
                                    });
                            });
                        });
                });
            });

        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }

        return $query;
    }

    public function blockedSeatIds(): \Illuminate\Support\Collection
    {
        if (! $this->venue_id) {
            return collect();
        }

        return $this->blockedSeats()->pluck('seats.id')->unique()->values();
    }

    public function ticketTemplate(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TicketTemplate::class);
    }

    /**
     * Para secciones sin butacas: cantidad de entradas ya reservadas en esta sección
     * (tickets con section_id y reserva activa: INICIADO no expirada, PENDIENTE_PAGO, CONFIRMADO).
     */
    public function reservedCountForSection(int $sectionId): int
    {
        return ReservationTicket::query()
            ->active()
            ->where('section_id', $sectionId)
            ->whereHas('reservation', function ($q) {
                $q->where('event_id', $this->id)
                    ->where(function ($q2) {
                        $q2->whereIn('status', [
                            Reservation::STATUS_PENDIENTE_PAGO,
                            Reservation::STATUS_CONFIRMADO,
                        ])->orWhere(function ($q3) {
                            $q3->where('status', Reservation::STATUS_INICIADO)
                                ->where(function ($q4) {
                                    $q4->whereNull('reservations.expires_at')
                                        ->orWhere('reservations.expires_at', '>', now());
                                });
                        });
                    });
            })
            ->count();
    }

    /**
     * Para secciones sin butacas: entradas disponibles = capacity - reserved.
     */
    public function availableCapacityForSection(Section $section): int
    {
        if ($section->has_seats || $section->capacity === null) {
            return 0;
        }
        $reserved = $this->reservedCountForSection($section->id);

        return max(0, $section->capacity - $reserved);
    }

    /**
     * IDs de butacas actualmente ocupadas para este evento (reserva confirmada, pendiente de pago o en proceso no expirada).
     */
    public function occupiedSeatIds(): \Illuminate\Support\Collection
    {
        if (! $this->venue_id) {
            return collect();
        }

        return \App\Models\ReservationTicket::query()
            ->active()
            ->whereNotNull('seat_id')
            ->whereHas('reservation', function ($q) {
                $q->where('event_id', $this->id)
                    ->where(function ($q2) {
                        $q2->whereIn('status', [
                            Reservation::STATUS_CONFIRMADO,
                            Reservation::STATUS_PENDIENTE_PAGO,
                        ])->orWhere(function ($q3) {
                            $q3->where('status', Reservation::STATUS_INICIADO)
                                ->where(function ($q4) {
                                    $q4->whereNull('reservations.expires_at')
                                        ->orWhere('reservations.expires_at', '>', now());
                                });
                        });
                    });
            })
            ->pluck('seat_id')
            ->unique()
            ->values();
    }
}
