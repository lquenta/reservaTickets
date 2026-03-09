<?php

namespace App\Console;

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
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
