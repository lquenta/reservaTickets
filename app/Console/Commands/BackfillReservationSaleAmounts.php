<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\ReservationPricingService;
use Illuminate\Console\Command;

class BackfillReservationSaleAmounts extends Command
{
    protected $signature = 'reservations:backfill-sale-amounts';

    protected $description = 'Calcula y guarda sale_amount en reservas CONFIRMADO sin monto';

    public function handle(ReservationPricingService $pricing): int
    {
        $query = Reservation::query()
            ->where('status', Reservation::STATUS_CONFIRMADO)
            ->whereNull('sale_amount');

        $count = $query->count();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->with(['event.sections', 'event.ticketTemplate', 'reservationTickets.seat'])
            ->chunkById(50, function ($reservations) use ($pricing, $bar) {
                foreach ($reservations as $reservation) {
                    $reservation->update(['sale_amount' => $pricing->totalForReservation($reservation)]);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Actualizadas {$count} reservas.");

        return self::SUCCESS;
    }
}
