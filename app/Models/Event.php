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
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** Secciones del evento (plano): solo las que el admin activó para este evento, con precio. */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'event_section')
            ->withPivot(['price', 'sort_order'])
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
            ->whereDoesntHave('reservationTickets', function ($q) {
                $q->whereHas('reservation', function ($q2) {
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
