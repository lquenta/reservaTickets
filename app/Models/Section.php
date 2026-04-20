<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'col_start',
        'col_end',
        'layout_color',
    ];

    protected function casts(): array
    {
        return [
            'has_seats' => 'boolean',
            'sort_order' => 'integer',
            'capacity' => 'integer',
            'row_start' => 'integer',
            'row_end' => 'integer',
            'col_start' => 'integer',
            'col_end' => 'integer',
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

    /**
     * Restringe una consulta de butacas del venue a filas/columnas definidas para esta sección.
     * Si col_start/col_end son null, se consideran todas las columnas del venue.
     */
    public function applySeatSpatialConstraints(Builder $query): Builder
    {
        if (! $this->has_seats) {
            return $query;
        }
        if ($this->row_start !== null && $this->row_end !== null) {
            $r1 = min($this->row_start, $this->row_end);
            $r2 = max($this->row_start, $this->row_end);
            $query->whereBetween('row', [$r1, $r2]);
        }
        if ($this->col_start !== null && $this->col_end !== null) {
            $c1 = min($this->col_start, $this->col_end);
            $c2 = max($this->col_start, $this->col_end);
            $query->whereBetween('number', [$c1, $c2]);
        }

        return $query;
    }

    /** Indica si una butaca (fila y número del venue) cae dentro del rectángulo de esta sección. */
    public function containsSeat(int $row, int $number): bool
    {
        if (! $this->has_seats) {
            return false;
        }
        if ($this->row_start === null || $this->row_end === null) {
            return false;
        }
        $r1 = min($this->row_start, $this->row_end);
        $r2 = max($this->row_start, $this->row_end);
        if ($row < $r1 || $row > $r2) {
            return false;
        }
        if ($this->col_start !== null && $this->col_end !== null) {
            $c1 = min($this->col_start, $this->col_end);
            $c2 = max($this->col_start, $this->col_end);
            if ($number < $c1 || $number > $c2) {
                return false;
            }
        }

        return true;
    }
}
