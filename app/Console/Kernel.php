<?php

namespace App\Console;

use App\Jobs\PruneAnalyticsMetricsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('reservations:cancel-expired')->everyMinute();

        // Procesar cola de jobs (envío de correos, etc.) cada minuto; termina cuando no hay más jobs
        $schedule->command('queue:work database --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping(2);

        // Depura metricas de analytics con mas de 30 dias de antiguedad.
        $schedule->job(new PruneAnalyticsMetricsJob())
            ->monthlyOn(1, '03:30');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
