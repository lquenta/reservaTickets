<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'name',
        'slug',
        'sort_order',
        'has_seats',
        'capacity',
        'row_start',
        'row_end',
    ];

    protected function casts(): array
    {
        return [
            'has_seats' => 'boolean',
            'sort_order' => 'integer',
            'capacity' => 'integer',
            'row_start' => 'integer',
            'row_end' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Section $section) {
            if (empty($section->slug)) {
                $section->slug = Str::slug($section->name);
            }
        });
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_section')
            ->withPivot(['price', 'sort_order'])
            ->withTimestamps();
    }
}
