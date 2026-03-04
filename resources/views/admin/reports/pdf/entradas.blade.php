<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte - Entradas vendidas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        h1 { font-size: 18px; color: #6d28d9; margin: 0 0 8px 0; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 16px; }
        .total-box { background: #ede9fe; border: 1px solid #8b5cf6; padding: 12px 16px; margin-bottom: 20px; display: inline-block; }
        .total-label { font-size: 10px; font-weight: bold; color: #6d28d9; text-transform: uppercase; }
        .total-value { font-size: 22px; font-weight: bold; color: #5b21b6; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        td.num { text-align: right; }
        .footer { margin-top: 24px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Reporte: Entradas vendidas</h1>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}. Reservas confirmadas.</p>

    <div class="total-box">
        <p class="total-label">Total entradas vendidas</p>
        <p class="total-value">{{ number_format($ticketsSoldTotal) }}</p>
    </div>

    @if($ticketsSoldByEvent->isNotEmpty())
        <p style="font-weight: bold; margin-bottom: 8px;">Por evento</p>
        <table>
            <thead>
                <tr>
                    <th>Evento</th>
                    <th class="num">Entradas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ticketsSoldByEvent as $eventId => $row)
                    <tr>
                        <td>{{ $eventsForTickets->get($eventId)?->name ?? 'Evento #'.$eventId }}</td>
                        <td class="num">{{ number_format($row->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Aún no hay entradas vendidas (ninguna reserva confirmada).</p>
    @endif

    <p class="footer">NOVA - Reporte administrativo</p>
</body>
</html>
