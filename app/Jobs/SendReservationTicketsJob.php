<?php

namespace App\Jobs;

use App\Mail\TicketsSentMail;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendReservationTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public bool $force = false,
        /** Admin authorize must email tickets; bypass tickets_emailed_at idempotency. */
        public bool $fromAuthorize = false,
    ) {}

    public function handle(): void
    {
        $reservation = $this->reservation->fresh();
        if (! $reservation) {
            return;
        }

        if ($reservation->status !== Reservation::STATUS_CONFIRMADO) {
            return;
        }

        $reservation->load(['user', 'soldBy', 'event', 'reservationTickets.seat']);
        $email = $reservation->ticketDeliveryEmail();
        if (! $email) {
            return;
        }

        if (! $this->force && ! $this->fromAuthorize && $reservation->hasTicketsEmailed()) {
            return;
        }

        Mail::to($email)->send(new TicketsSentMail($reservation));
        $reservation->update(['tickets_emailed_at' => now()]);
    }
}
