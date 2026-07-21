@extends('layouts.admin')

@section('title', 'Dashboard de métricas')

@section('admin')
@php
    $topIps = $metrics['ip_activity_last_10_days']->take(5);
    $chartPayload = [
        'trend' => $metrics['trend']->values()->map(fn ($row) => [
            'day' => $row['day'],
            'visits' => (int) $row['visits'],
            'conversions' => (int) $row['conversions'],
            'sales' => (float) $row['sales'],
        ])->all(),
        'salesByEvent' => $metrics['sales_by_event']->map(fn ($row) => [
            'label' => $row->event_name,
            'total' => (float) $row->total,
        ])->values()->all(),
    ];
@endphp
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Dashboard de métricas</h1>
            <p class="text-slate-600 dark:text-slate-400">Visitas, conversiones, ventas, reservas y asistencia del periodo seleccionado.</p>
        </div>
        <a href="{{ route('admin.reports.metrics', request()->query()) }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-violet-500 px-4 py-2.5 text-violet-700 dark:text-violet-200 font-semibold hover:bg-violet-50 dark:hover:bg-violet-900/30 transition">
            <span aria-hidden="true">📄</span> Ver reporte
        </a>
    </div>

    <form method="GET" action="{{ route('admin.dashboard') }}" class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-4 md:p-6 grid md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-semibold mb-1 text-slate-700 dark:text-slate-300">Desde</label>
            <input type="date" name="date_from" value="{{ $filters['date_from']->toDateString() }}" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1 text-slate-700 dark:text-slate-300">Hasta</label>
            <input type="date" name="date_to" value="{{ $filters['date_to']->toDateString() }}" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1 text-slate-700 dark:text-slate-300">Eventos</label>
            <select name="event_scope" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                <option value="active" @selected($filters['event_scope'] === 'active')>Solo activos</option>
                <option value="all" @selected($filters['event_scope'] === 'all')>Todos</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1 text-slate-700 dark:text-slate-300">Evento específico</label>
            <select name="event_id" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                <option value="">Todos</option>
                @foreach($eventsForFilter as $ev)
                    <option value="{{ $ev->id }}" @selected((int) $filters['event_id'] === (int) $ev->id)>{{ $ev->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-4 flex flex-wrap justify-end gap-2">
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-300 dark:border-slate-600 px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-200">Limpiar</a>
            <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-700 px-4 py-2.5 font-semibold text-white">Aplicar filtros</button>
        </div>
    </form>

    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-violet-200/60 dark:border-violet-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Visitas</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['visits']) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-violet-200/60 dark:border-violet-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Conversiones</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['conversions']) }}</p>
            <p class="text-sm text-emerald-600 dark:text-emerald-300 mt-1">{{ number_format($metrics['kpis']['conversion_rate'], 2) }}%</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-violet-200/60 dark:border-violet-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ventas brutas</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['sales_total'], 2) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-orange-200/60 dark:border-orange-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Reembolsos</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['refunds_total'], 2) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-emerald-200/60 dark:border-emerald-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ventas netas</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['net_sales'], 2) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-violet-200/60 dark:border-violet-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Publico confirmado</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['confirmed_audience']) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-violet-200/60 dark:border-violet-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Reservado / pendiente</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['reserved_pending']) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-violet-200/60 dark:border-violet-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Asistencia confirmada</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['attendance_confirmed']) }}</p>
        </div>
    </div>

    @if($metrics['alerts']['high_pending'] || $metrics['alerts']['low_conversion'])
        <div class="rounded-2xl border-2 border-amber-500 bg-amber-900/30 p-4 text-amber-100">
            <p class="font-semibold mb-2">Alertas</p>
            @if($metrics['alerts']['high_pending'])
                <p class="text-sm">Existe un volumen alto de reservas pendientes.</p>
            @endif
            @if($metrics['alerts']['low_conversion'])
                <p class="text-sm">La conversion del periodo esta por debajo del 5%.</p>
            @endif
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Tendencia diaria</h2>
            @if($metrics['trend']->isNotEmpty())
                <div class="h-[280px]">
                    <canvas id="dashboard-trend-chart" aria-label="Grafica de tendencia diaria"></canvas>
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400">Sin datos de tendencia para el rango elegido.</p>
            @endif
        </div>
        <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Ventas por evento</h2>
            @if($metrics['sales_by_event']->isNotEmpty())
                <div class="h-[280px]">
                    <canvas id="dashboard-sales-chart" aria-label="Grafica de ventas por evento"></canvas>
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400">Sin ventas registradas en el periodo.</p>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-bold text-slate-800 dark:text-white">Resumen por evento</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px]">
                    <thead class="bg-slate-100 dark:bg-slate-700/50">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Evento</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Visitas</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Conversiones</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Tasa</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Ventas</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Confirmado</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($metrics['events_table'] as $row)
                            <tr class="border-t border-slate-200 dark:border-slate-700">
                                <td class="px-4 py-3 text-slate-800 dark:text-white">{{ $row->event_name }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($row->visits) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($row->conversions) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($row->conversion_rate, 2) }}%</td>
                                <td class="px-4 py-3 text-right">{{ number_format($row->sales_total, 2) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($row->confirmed_audience) }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($row->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-600 text-white px-2.5 py-0.5 text-xs font-semibold">Activo</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-500 text-white px-2.5 py-0.5 text-xs font-semibold">Inactivo</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">Sin datos para los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-5">
                <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Operacion</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 dark:bg-slate-700/50 px-3 py-2.5">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Pendientes</span>
                        <span class="text-lg font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['reserved_pending']) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 dark:bg-slate-700/50 px-3 py-2.5">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Asistencia</span>
                        <span class="text-lg font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['attendance_confirmed']) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-100 dark:bg-slate-700/50 px-3 py-2.5">
                        <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Publico confirmado</span>
                        <span class="text-lg font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['confirmed_audience']) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-5">
                <div class="flex items-start justify-between gap-2 mb-4">
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">Top IPs (10 dias)</h2>
                    <a href="{{ route('admin.reports.metrics', request()->query()) }}" class="text-xs font-semibold text-violet-600 dark:text-violet-300 hover:underline shrink-0">Ver detalle</a>
                </div>
                @forelse($topIps as $ipRow)
                    <div class="border-t border-slate-200 dark:border-slate-700 py-3 first:border-t-0 first:pt-0">
                        <p class="font-mono text-sm text-slate-800 dark:text-white">{{ $ipRow->ip_address }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $ipRow->geo_label ?? '—' }}</p>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mt-1">{{ number_format($ipRow->visits_total) }} visitas</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">Sin visitas por IP en los ultimos 10 dias.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="dashboard-chart-data">@json($chartPayload)</script>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dataEl = document.getElementById('dashboard-chart-data');
    if (!dataEl || typeof Chart === 'undefined') {
        return;
    }

    var payload;
    try {
        payload = JSON.parse(dataEl.textContent);
    } catch (e) {
        return;
    }

    var isDark = document.documentElement.classList.contains('dark');
    var tickColor = isDark ? '#cbd5e1' : '#475569';
    var gridColor = isDark ? 'rgba(148, 163, 184, 0.15)' : 'rgba(148, 163, 184, 0.25)';
    var tooltipBg = isDark ? '#0f172a' : '#ffffff';
    var tooltipFg = isDark ? '#e2e8f0' : '#0f172a';

    var palette = ['#7c3aed', '#e11d8a', '#06b6d4', '#10b981', '#f59e0b', '#6366f1', '#f43f5e', '#14b8a6'];

    function formatDayLabel(day) {
        var parts = String(day).split('-');
        if (parts.length !== 3) {
            return day;
        }
        return parts[2] + '/' + parts[1];
    }

    var trendCanvas = document.getElementById('dashboard-trend-chart');
    if (trendCanvas && Array.isArray(payload.trend) && payload.trend.length) {
        var labels = payload.trend.map(function (row) { return formatDayLabel(row.day); });
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Visitas',
                        data: payload.trend.map(function (row) { return row.visits; }),
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.15)',
                        tension: 0.35,
                        fill: true,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Conversiones',
                        data: payload.trend.map(function (row) { return row.conversions; }),
                        borderColor: '#e11d8a',
                        backgroundColor: 'rgba(225, 29, 138, 0.12)',
                        tension: 0.35,
                        fill: false,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Ventas (Bs)',
                        data: payload.trend.map(function (row) { return row.sales; }),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        tension: 0.35,
                        fill: false,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        labels: { color: tickColor },
                    },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipFg,
                        bodyColor: tooltipFg,
                        borderColor: gridColor,
                        borderWidth: 1,
                    },
                },
                scales: {
                    x: {
                        ticks: { color: tickColor, maxRotation: 0 },
                        grid: { color: gridColor },
                    },
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        ticks: { color: tickColor, precision: 0 },
                        grid: { color: gridColor },
                        title: { display: true, text: 'Visitas / Conv', color: tickColor },
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        ticks: { color: tickColor },
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Ventas Bs', color: tickColor },
                    },
                },
            },
        });
    }

    var salesCanvas = document.getElementById('dashboard-sales-chart');
    if (salesCanvas && Array.isArray(payload.salesByEvent) && payload.salesByEvent.length) {
        new Chart(salesCanvas, {
            type: 'doughnut',
            data: {
                labels: payload.salesByEvent.map(function (row) { return row.label; }),
                datasets: [{
                    data: payload.salesByEvent.map(function (row) { return row.total; }),
                    backgroundColor: payload.salesByEvent.map(function (_, i) {
                        return palette[i % palette.length];
                    }),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: tickColor,
                            boxWidth: 12,
                            padding: 12,
                        },
                    },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipFg,
                        bodyColor: tooltipFg,
                        borderColor: gridColor,
                        borderWidth: 1,
                        callbacks: {
                            label: function (ctx) {
                                var value = Number(ctx.raw || 0);
                                return ' ' + ctx.label + ': ' + value.toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                }) + ' Bs';
                            },
                        },
                    },
                },
            },
        });
    }
});
</script>
@endpush
