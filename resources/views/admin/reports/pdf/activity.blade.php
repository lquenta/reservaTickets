<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte - Actividad de usuarios</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; padding: 16px; }
        h1 { font-size: 16px; color: #6d28d9; margin: 0 0 8px 0; }
        h2 { font-size: 12px; margin: 14px 0 6px; }
        .subtitle { font-size: 9px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        .footer { margin-top: 16px; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <h1>Actividad de usuarios</h1>
    <p class="subtitle">
        Generado el {{ now()->format('d/m/Y H:i') }}.
        Período {{ $filters['date_from']->format('d/m/Y') }} – {{ $filters['date_to']->format('d/m/Y') }}.
        Hasta 400 filas del detalle.
    </p>

    <p>
        Páginas: {{ $summary['page_views'] }}.
        Acciones: {{ $summary['actions'] }}.
        Cuentas: {{ $summary['users'] }}.
        Visitas: {{ $summary['sessions'] }}.
        Tiempo medio: {{ $duration::format($summary['avg_ms']) }}.
    </p>

    <h2>Por persona</h2>
    <table>
        <thead>
            <tr>
                <th>Persona</th>
                <th>Páginas</th>
                <th>Acciones</th>
                <th>Tiempo visible</th>
            </tr>
        </thead>
        <tbody>
            @forelse($actors as $actor)
                <tr>
                    <td>{{ $actor->name }}{{ $actor->email ? ' ('.$actor->email.')' : '' }}</td>
                    <td>{{ $actor->pages }}</td>
                    <td>{{ $actor->actions }}</td>
                    <td>{{ $duration::format($actor->visible_ms) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sin actividad.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Detalle</h2>
    <table>
        <thead>
            <tr>
                <th>Cuándo</th>
                <th>Persona</th>
                <th>Qué hizo</th>
                <th>Dónde</th>
                <th>Tiempo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->occurred_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? 'Invitado' }}</td>
                    <td>{{ $log->actionLabel() }}{{ $log->label ? ' · '.$log->label : '' }}</td>
                    <td>{{ $routes::page($log->route_name, $log->path) }}</td>
                    <td>{{ $log->event_name === \App\Models\AnalyticsEvent::EVENT_PAGE_VIEW ? $duration::format($log->duration_ms) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">No incluye el texto escrito en formularios. El tiempo es el que la pestaña estuvo visible.</p>
</body>
</html>
