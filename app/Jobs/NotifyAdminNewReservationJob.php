<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\TelegramNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAdminNewReservationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {}

    public function handle(TelegramNotificationService $telegram): void
    {
        $this->reservation->load('event');
        $telegram->sendNewReservationPending($this->reservation);
    }
}
