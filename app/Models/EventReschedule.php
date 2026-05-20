<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventReschedule extends Model
{
    protected $fillable = [
        'event_id',
        'previous_starts_at',
        'new_starts_at',
        'reason',
        'performed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'previous_starts_at' => 'datetime',
            'new_starts_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
