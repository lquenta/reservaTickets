<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de metricas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { margin: 0 0 6px; font-size: 20px; }
        .muted { color: #555; margin-bottom: 14px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .grid th, .grid td { border: 1px solid #ccc; padding: 8px; }
        .grid th { background: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Reporte de metricas</h1>
    <p class="muted">Rango: {{ $filters['date_from']->format('d/m/Y') }} - {{ $filters['date_to']->format('d/m/Y') }} | Eventos: {{ $filters['event_scope'] === 'all' ? 'Todos' : 'Solo activos' }}</p>

    <table class="grid">
        <thead>
            <tr>
                <th>Visitas</th>
                <th>Conversiones</th>
                <th>Tasa conversion</th>
                <th>Ventas</th>
                <th>Publico confirmado</th>
                <th>Reservado / pendiente</th>
                <th>Asistencia confirmada</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-right">{{ number_format($metrics['kpis']['visits']) }}</td>
                <td class="text-right">{{ number_format($metrics['kpis']['conversions']) }}</td>
                <td class="text-right">{{ number_format($metrics['kpis']['conversion_rate'], 2) }}%</td>
                <td class="text-right">{{ number_format($metrics['kpis']['sales_total'], 2) }}</td>
                <td class="text-right">{{ number_format($metrics['kpis']['confirmed_audience']) }}</td>
                <td class="text-right">{{ number_format($metrics['kpis']['reserved_pending']) }}</td>
                <td class="text-right">{{ number_format($metrics['kpis']['attendance_confirmed']) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th>Evento</th>
                <th class="text-right">Visitas</th>
                <th class="text-right">Conversiones</th>
                <th class="text-right">Tasa</th>
                <th class="text-right">Ventas</th>
                <th class="text-right">Confirmado</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($metrics['events_table'] as $row)
                <tr>
                    <td>{{ $row->event_name }}</td>
                    <td class="text-right">{{ number_format($row->visits) }}</td>
                    <td class="text-right">{{ number_format($row->conversions) }}</td>
                    <td class="text-right">{{ number_format($row->conversion_rate, 2) }}%</td>
                    <td class="text-right">{{ number_format($row->sales_total, 2) }}</td>
                    <td class="text-right">{{ number_format($row->confirmed_audience) }}</td>
                    <td>{{ $row->is_active ? 'Activo' : 'Inactivo' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Sin datos para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 style="margin: 10px 0 6px; font-size: 16px;">Detalle por IP (ultimos 10 dias)</h2>
    <table class="grid">
        <thead>
            <tr>
                <th>IP</th>
                <th class="text-right">Visitas (10d)</th>
                <th>Ultima visita</th>
                <th>Detalle diario</th>
            </tr>
        </thead>
        <tbody>
            @forelse($metrics['ip_activity_last_10_days'] as $row)
                <tr>
                    <td>{{ $row->ip_address }}</td>
                    <td class="text-right">{{ number_format($row->visits_total) }}</td>
                    <td>{{ $row->last_day ? \Illuminate\Support\Carbon::parse($row->last_day)->format('d/m/Y') : '—' }}</td>
                    <td>
                        @foreach($row->daily as $daily)
                            {{ \Illuminate\Support\Carbon::parse($daily['day'])->format('d/m') }}: {{ $daily['total'] }}@if(! $loop->last), @endif
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No hay visitas por IP registradas en los ultimos 10 dias.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
