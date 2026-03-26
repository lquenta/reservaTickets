<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'seat_id',
        'section_id',
        'holder_name',
        'position',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
