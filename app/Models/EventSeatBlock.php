<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventSeatBlock extends Pivot
{
    protected $table = 'event_seat_blocks';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public static function applyActiveConstraint(Builder $query): void
    {
        $query->where(function (Builder $inner) {
            $inner->whereNull('event_seat_blocks.expires_at')
                ->orWhere('event_seat_blocks.expires_at', '>', now());
        });
    }
}
