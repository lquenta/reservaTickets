<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'plan_image_path',
        'seat_rows',
        'seat_columns',
        'layout_canvas_width',
        'layout_canvas_height',
    ];

    protected function casts(): array
    {
        return [
            'seat_rows' => 'integer',
            'seat_columns' => 'integer',
            'layout_canvas_width' => 'integer',
            'layout_canvas_height' => 'integer',
        ];
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function layoutElements(): HasMany
    {
        return $this->hasMany(VenueLayoutElement::class)->orderBy('z_index')->orderBy('id');
    }
}
