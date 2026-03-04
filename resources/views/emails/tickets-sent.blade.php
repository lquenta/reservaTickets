<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #1f2937; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        h1 { color: #6d28d9; }
        .ticket { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 12px 0; background: #f9fafb; }
        .ticket-info { margin: 0 0 10px 0; }
        .ticket-qr { margin: 0; }
        .ticket-qr img { display: block; width: 80px; height: 80px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>¡Tus tickets han sido confirmados!</h1>
        <p>Hola {{ $reservation->user->name }},</p>
        <p>Tu reserva para <strong>{{ $reservation->event->name }}</strong> ha sido autorizada.</p>
        <p><strong>Fecha:</strong> {{ $reservation->event->starts_at->translatedFormat('l d F Y, H:i') }}</p>
        <p><strong>Lugar:</strong> {{ $reservation->event->venue }}</p>
        <h2>Tickets</h2>
        @foreach($reservation->reservationTickets as $ticket)
            <div class="ticket">
                <p class="ticket-info"><strong>Ticket {{ $ticket->position }}</strong>: {{ $ticket->holder_name }}@if($ticket->seat) — {{ $reservation->event->ticketTemplate?->design['seat_label'] ?? 'Butaca' }} {{ $ticket->seat->display_label }}@endif</p>
                <p class="ticket-qr"><img src="{{ \App\Support\TicketQrCode::dataUri($reservation->payment_code, $ticket->position, 80) }}" alt="QR entrada" width="80" height="80" /></p>
            </div>
        @endforeach
        <p>Presenta el QR del ticket o el PDF adjunto en la entrada.</p>
        <p>NOVA — Tickets de entrada 2026</p>
    </div>
</body>
</html>
