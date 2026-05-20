<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de reembolsos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
        .text-right { text-align: right; }
        .summary { margin: 12px 0; }
    </style>
</head>
<body>
    <h1>Reporte de reembolsos</h1>
    <p class="meta">Generado: {{ now()->format('d/m/Y H:i') }}</p>
    <div class="summary">
        <strong>Total reembolsos:</strong> {{ $refundsCount }} &nbsp;|&nbsp;
        <strong>Monto:</strong> {{ number_format($refundsTotal, 2) }} Bs
    </div>
    @if($refundsByEvent->isNotEmpty())
        <h2>Por evento</h2>
        <table>
            <thead><tr><th>Evento</th><th class="text-right">Cantidad</th><th class="text-right">Total Bs</th></tr></thead>
            <tbody>
                @foreach($refundsByEvent as $row)
                    <tr>
                        <td>{{ $row->event_name }}</td>
                        <td class="text-right">{{ $row->count }}</td>
                        <td class="text-right">{{ number_format($row->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    @if($refundedReservations->isNotEmpty())
        <h2>Detalle</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Evento</th>
                    <th>Código</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($refundedReservations as $r)
                    <tr>
                        <td>{{ $r->refunded_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $r->user?->name }} ({{ $r->user?->email }})</td>
                        <td>{{ $r->event?->name }}</td>
                        <td>{{ $r->payment_code }}</td>
                        <td class="text-right">{{ number_format($r->refund_amount ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
