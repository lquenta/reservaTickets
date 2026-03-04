<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'design',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'design' => 'array',
            'price' => 'decimal:2',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public static function defaultDesign(): array
    {
        return [
            'title' => 'Entrada',
            'subtitle' => '',
            'price_label' => 'Precio',
            'seat_label' => 'Butaca',
        ];
    }
}
