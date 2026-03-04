<?php

namespace App\Jobs;

use App\Mail\TicketsSentMail;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendReservationTicketsJob
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {}

    public function handle(): void
    {
        $this->reservation->load(['user', 'event', 'reservationTickets.seat']);
        Mail::to($this->reservation->user->email)->send(new TicketsSentMail($this->reservation));
    }
}
