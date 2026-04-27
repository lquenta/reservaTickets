<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PruneAnalyticsMetricsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        AnalyticsEvent::query()
            ->where('occurred_at', '<', now()->subDays(30))
            ->delete();
    }
}
