<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; padding: 20px; line-height: 1.6; }
        .ticket { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 12px 0; background: #f9fafb; page-break-inside: avoid; }
        .ticket-info { margin: 0 0 10px 0; }
        .ticket-qr { margin: 0; }
        .ticket-qr img { display: block; width: 80px; height: 80px; }
    </style>
</head>
<body>
    @foreach($tickets as $ticket)
        <div class="ticket">
            <p class="ticket-info"><strong>Ticket {{ $ticket->position }}</strong>: {{ $ticket->holder_name }}@if($ticket->seat) — {{ $design['seat_label'] ?? 'Butaca' }} {{ $ticket->seat->display_label }}@endif</p>
            <p class="ticket-qr"><img src="{{ \App\Support\TicketQrCode::dataUri($reservation->payment_code, $ticket->position, 80) }}" alt="QR entrada" width="80" height="80" /></p>
        </div>
    @endforeach
</body>
</html>
