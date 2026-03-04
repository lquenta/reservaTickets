<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte - Ventas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        h1 { font-size: 18px; color: #6d28d9; margin: 0 0 8px 0; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 16px; }
        .total-box { background: #d1fae5; border: 1px solid #059669; padding: 12px 16px; margin-bottom: 20px; display: inline-block; }
        .total-label { font-size: 10px; font-weight: bold; color: #047857; text-transform: uppercase; }
        .total-value { font-size: 20px; font-weight: bold; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        td.num { text-align: right; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .footer { margin-top: 24px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Reporte de ventas</h1>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }} — Entradas vendidas por evento (reservas confirmadas).</p>

    @if($salesByEvent->isNotEmpty())
        <div class="total-box">
            <p class="total-label">Total ventas</p>
            <p class="total-value">{{ number_format($salesTotal, 2) }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Evento</th>
                    <th class="num">Entradas</th>
                    <th class="num">Precio unit.</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesByEvent as $row)
                    <tr>
                        <td>{{ $row->event_name }}</td>
                        <td class="num">{{ number_format($row->tickets_sold) }}</td>
                        <td class="num">{{ number_format($row->unit_price, 2) }}</td>
                        <td class="num">{{ number_format($row->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="num">Total</td>
                    <td class="num">{{ number_format($salesTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <p>Aún no hay ventas (reservas confirmadas) para mostrar.</p>
    @endif

    <p class="footer">NOVA — Reporte administrativo</p>
</body>
</html>
