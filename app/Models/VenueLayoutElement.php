<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueLayoutElement extends Model
{
    use HasFactory;

    public const TYPE_SEAT = 'seat';

    public const TYPE_STAGE = 'stage';

    public const TYPE_SPEAKER = 'speaker';

    protected $fillable = [
        'venue_id',
        'seat_id',
        'type',
        'x',
        'y',
        'w',
        'h',
        'rotation',
        'z_index',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'w' => 'float',
            'h' => 'float',
            'rotation' => 'float',
            'z_index' => 'integer',
            'meta' => 'array',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }
}
