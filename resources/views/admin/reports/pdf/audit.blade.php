<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte - Auditoría de reservas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; padding: 16px; }
        h1 { font-size: 16px; color: #6d28d9; margin: 0 0 8px 0; }
        .subtitle { font-size: 9px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        td.detail { max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
        .success { color: #047857; }
        .failed { color: #b91c1c; }
        .footer { margin-top: 16px; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <h1>Auditoría de reservas</h1>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }} — Últimos 500 registros según filtros.</p>

    @if($logs->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Acción</th>
                    <th>Resultado</th>
                    <th>Usuario</th>
                    <th>Evento</th>
                    <th>IP</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $actionLabels[$log->action] ?? $log->action }}</td>
                        <td class="{{ $log->result === 'success' ? 'success' : 'failed' }}">{{ $log->result === 'success' ? 'Éxito' : 'Fallido' }}</td>
                        <td>{{ $log->user ? $log->user->name . ' (' . $log->user->email . ')' : '—' }}</td>
                        <td>{{ $log->event?->name ?? '—' }}</td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                        <td class="detail">{{ Str::limit($log->message ?? '—', 50) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="footer">Total: {{ $logs->count() }} registro(s).</p>
    @else
        <p>No hay registros de auditoría para exportar.</p>
    @endif
</body>
</html>
