<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'section_id',
        'row',
        'number',
        'label',
        'blocked',
    ];

    protected function casts(): array
    {
        return [
            'blocked' => 'boolean',
            'row' => 'integer',
            'number' => 'integer',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function reservationTickets(): HasMany
    {
        return $this->hasMany(ReservationTicket::class);
    }

    /** Fila como letra del abecedario: 1=A, 2=B, ... 26=Z */
    public function getRowLetterAttribute(): string
    {
        $row = (int) $this->row;
        return $row >= 1 && $row <= 26 ? chr(64 + $row) : (string) $this->row;
    }

    /** Etiqueta para mostrar: fila letra + número (ej. A-1, B-3). Usar en vistas y reportes. */
    public function getDisplayLabelAttribute(): string
    {
        return $this->row_letter . '-' . $this->number;
    }
}
