<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketsSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tus tickets - ' . $this->reservation->event->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets-sent',
        );
    }

    public function attachments(): array
    {
        $reservation = $this->reservation->load(['event.ticketTemplate', 'reservationTickets.seat', 'soldBy']);
        $template = $reservation->event->ticketTemplate;
        $design = $template ? $template->design : \App\Models\TicketTemplate::defaultDesign();
        $price = $template ? (float) $template->price : 0;

        $activeTickets = $reservation->reservationTickets->filter(fn ($t) => ! $t->isRefunded())->values();

        $pdf = Pdf::loadView('tickets.pdf', [
            'reservation' => $reservation,
            'event' => $reservation->event,
            'tickets' => $activeTickets,
            'design' => $design,
            'price' => $price,
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'tickets-' . $reservation->payment_code . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
