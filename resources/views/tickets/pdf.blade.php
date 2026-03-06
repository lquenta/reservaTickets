<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; padding: 20px; line-height: 1.5; }
        .ticket { border: 2px solid #8b5cf6; border-radius: 16px; padding: 24px 28px; margin: 16px 0; background: #fff; page-break-inside: avoid; max-width: 400px; }
        .ticket-title { font-size: 18px; font-weight: bold; color: #6d28d9; border-bottom: 1px solid #c4b5fd; padding-bottom: 12px; margin: 0 0 8px 0; }
        .ticket-subtitle { font-size: 11px; color: #64748b; margin: 0 0 12px 0; min-height: 1em; }
        .ticket-event { font-size: 13px; font-weight: 600; color: #1e293b; margin: 0 0 4px 0; }
        .ticket-datetime { font-size: 11px; color: #475569; margin: 0 0 12px 0; }
        .ticket-holder { font-size: 13px; font-weight: bold; color: #1e293b; margin: 0 0 12px 0; }
        .ticket-holder span { font-weight: normal; color: #475569; }
        .ticket-seat { background: #f1f5f9; border: 1px dashed #94a3b8; border-radius: 8px; padding: 10px 12px; margin: 0 0 16px 0; }
        .ticket-seat-label { font-weight: bold; color: #1e293b; }
        .ticket-seat-value { font-family: monospace; color: #6d28d9; }
        .ticket-qr { margin: 12px 0 0 0; }
        .ticket-qr img { display: block; width: 80px; height: 80px; }
    </style>
</head>
<body>
    @php
        $design = $design ?? \App\Models\TicketTemplate::defaultDesign();
        $title = $design['title'] ?? 'Entrada';
        $subtitle = $design['subtitle'] ?? '';
        $seatLabel = $design['seat_label'] ?? 'Butaca';
        $eventDate = $event->starts_at->translatedFormat('l d F Y, H:i');
        $eventVenue = $event->venue ?? '';
    @endphp
    @foreach($tickets as $ticket)
        <div class="ticket">
            <p class="ticket-title">{{ $title }}</p>
            @if($subtitle !== '')
                <p class="ticket-subtitle">{{ $subtitle }}</p>
            @endif
            <p class="ticket-event">{{ $event->name }}</p>
            <p class="ticket-datetime">{{ $eventDate }} · {{ $eventVenue }}</p>
            <p class="ticket-holder">Titular: <span>{{ $ticket->holder_name }}</span></p>
            <div class="ticket-seat">
                <span class="ticket-seat-label">{{ $seatLabel }}</span>: <span class="ticket-seat-value">@if($ticket->seat){{ $ticket->seat->display_label }}@elseif($ticket->section){{ $ticket->section->name }}@else—@endif</span>
            </div>
            <p class="ticket-qr"><img src="{{ \App\Support\TicketQrCode::dataUri($reservation->payment_code, $ticket->position, 80) }}" alt="QR entrada" width="80" height="80" /></p>
        </div>
    @endforeach
</body>
</html>
