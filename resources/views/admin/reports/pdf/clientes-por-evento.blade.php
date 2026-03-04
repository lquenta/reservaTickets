<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte - Clientes por evento</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        h1 { font-size: 18px; color: #6d28d9; margin: 0 0 8px 0; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 20px; }
        .event-block { margin-bottom: 24px; page-break-inside: avoid; }
        .event-header { background: #ede9fe; border: 1px solid #8b5cf6; padding: 10px 12px; margin-bottom: 0; }
        .event-name { font-weight: bold; font-size: 13px; color: #5b21b6; }
        .event-meta { font-size: 10px; color: #666; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        td.num { text-align: right; }
        .footer { margin-top: 24px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Reporte: Clientes por evento</h1>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}. Clientes que reservaron y confirmaron el ticket, agrupados por evento.</p>

    @if($eventsWithClients->isNotEmpty())
        @foreach($eventsWithClients as $event)
            <div class="event-block">
                <div class="event-header">
                    <p class="event-name">{{ $event->name }}</p>
                    <p class="event-meta">{{ $event->starts_at->translatedFormat('d/m/Y H:i') }} · {{ $event->venue }}</p>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Email / Teléfono</th>
                            <th class="num">Tickets</th>
                            <th>Butacas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($event->reservations as $res)
                            <tr>
                                <td>{{ $res->user->name }}</td>
                                <td>{{ $res->user->email }} · {{ $res->user->phone ?? '—' }}</td>
                                <td class="num">{{ $res->reservationTickets->count() }}</td>
                                <td>{{ $res->reservationTickets->map(fn($t) => $t->seat?->display_label)->filter()->implode(', ') ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <p>No hay eventos con reservas confirmadas.</p>
    @endif

    <p class="footer">NOVA - Reporte administrativo</p>
</body>
</html>
