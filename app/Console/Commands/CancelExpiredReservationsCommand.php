<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class CancelExpiredReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela reservas en estado INICIADO cuya vigencia (expires_at) ya pasó.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Reservation::query()
            ->where('status', Reservation::STATUS_INICIADO)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => Reservation::STATUS_CANCELADO]);

        if ($count > 0) {
            $this->info("Se cancelaron {$count} reserva(s) expirada(s).");
        }

        return self::SUCCESS;
    }
}
