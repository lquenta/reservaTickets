<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte - Clientes que compraron</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        h1 { font-size: 18px; color: #6d28d9; margin: 0 0 8px 0; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 20px; }
        .client { border: 1px solid #e5e7eb; margin-bottom: 14px; padding: 12px; page-break-inside: avoid; }
        .client-name { font-weight: bold; font-size: 13px; margin-bottom: 4px; }
        .client-contact { color: #666; font-size: 10px; margin-bottom: 8px; }
        .client-events { margin-left: 12px; }
        .client-events li { margin: 4px 0; }
        .footer { margin-top: 24px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Reporte: Clientes que ya compraron entrada</h1>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }} — Usuarios con al menos una reserva confirmada.</p>

    @if($clientsWithTickets->isNotEmpty())
        @foreach($clientsWithTickets as $user)
            <div class="client">
                <p class="client-name">{{ $user->name }}</p>
                <p class="client-contact">{{ $user->email }} · {{ $user->phone ?? '—' }}</p>
                <ul class="client-events">
                    @foreach($user->reservations as $res)
                        <li><strong>{{ $res->event->name }}</strong> — {{ $res->reservationTickets->count() }} ticket(s)@if($res->reservationTickets->pluck('seat')->filter()->isNotEmpty()) ({{ $res->reservationTickets->map(fn($t) => $t->seat?->display_label)->filter()->implode(', ') }})@endif</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    @else
        <p>Ningún cliente ha completado una compra confirmada aún.</p>
    @endif

    <p class="footer">NOVA — Reporte administrativo</p>
</body>
</html>
