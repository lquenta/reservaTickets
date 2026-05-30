<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte - Nombres por evento</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        h1 { font-size: 18px; color: #6d28d9; margin: 0 0 8px 0; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 16px; }
        .event-title { background: #ede9fe; border: 1px solid #8b5cf6; padding: 10px 12px; margin: 0 0 14px 0; }
        .event-name { font-weight: bold; font-size: 13px; color: #5b21b6; margin: 0; }
        .event-meta { font-size: 10px; color: #666; margin: 4px 0 0 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .footer { margin-top: 24px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Reporte: Nombres por evento</h1>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }} — Reservas confirmadas, ordenadas por butaca.</p>

    <div class="event-title">
        <p class="event-name">{{ $event->name }}</p>
        <p class="event-meta">{{ $event->starts_at?->translatedFormat('d/m/Y H:i') ?? '—' }}</p>
    </div>

    @if($rows->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Butaca</th>
                    <th>Nombre completo</th>
                    <th>Fecha y hora</th>
                    <th>Reserva</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row->seat_label }}</td>
                        <td>{{ $row->holder_name }}</td>
                        <td>{{ $row->reserved_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $row->reservation }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No hay reservas confirmadas para este evento.</p>
    @endif

    <p class="footer">NOVA - Reporte administrativo</p>
</body>
</html>
