@extends('layouts.admin')

@section('title', 'Reporte de metricas')

@section('admin')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Reporte de metricas</h1>
            <p class="text-slate-600 dark:text-slate-400">Mismos KPIs del dashboard para consulta y exportacion.</p>
        </div>
        <a href="{{ route('admin.reports.pdf.metrics', request()->query()) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2.5 text-white font-semibold transition">
            <span aria-hidden="true">📄</span> Descargar PDF
        </a>
    </div>

    <form method="GET" action="{{ route('admin.reports.metrics') }}" class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-4 md:p-6 grid md:grid-cols-4 gap-4">
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
            <label class="block text-sm font-semibold mb-1 text-slate-700 dark:text-slate-300">Evento especifico</label>
            <select name="event_id" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                <option value="">Todos</option>
                @foreach($eventsForFilter as $ev)
                    <option value="{{ $ev->id }}" @selected((int) $filters['event_id'] === (int) $ev->id)>{{ $ev->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-4 flex justify-end">
            <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-700 px-4 py-2.5 font-semibold text-white">Aplicar filtros</button>
        </div>
    </form>

    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="rounded-xl border border-violet-200/60 dark:border-violet-700/50 p-4 bg-white dark:bg-slate-800/80"><p class="text-xs uppercase text-slate-500">Visitas</p><p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['visits']) }}</p></div>
        <div class="rounded-xl border border-violet-200/60 dark:border-violet-700/50 p-4 bg-white dark:bg-slate-800/80"><p class="text-xs uppercase text-slate-500">Conversiones</p><p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['conversions']) }}</p><p class="text-sm text-emerald-600 dark:text-emerald-300">{{ number_format($metrics['kpis']['conversion_rate'], 2) }}%</p></div>
        <div class="rounded-xl border border-violet-200/60 dark:border-violet-700/50 p-4 bg-white dark:bg-slate-800/80"><p class="text-xs uppercase text-slate-500">Ventas</p><p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['sales_total'], 2) }}</p></div>
        <div class="rounded-xl border border-violet-200/60 dark:border-violet-700/50 p-4 bg-white dark:bg-slate-800/80"><p class="text-xs uppercase text-slate-500">Publico confirmado</p><p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['confirmed_audience']) }}</p></div>
        <div class="rounded-xl border border-violet-200/60 dark:border-violet-700/50 p-4 bg-white dark:bg-slate-800/80"><p class="text-xs uppercase text-slate-500">Reservado / pendiente</p><p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['reserved_pending']) }}</p></div>
        <div class="rounded-xl border border-violet-200/60 dark:border-violet-700/50 p-4 bg-white dark:bg-slate-800/80"><p class="text-xs uppercase text-slate-500">Asistencia confirmada</p><p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['attendance_confirmed']) }}</p></div>
    </div>

    <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Detalle de visitas por IP (ultimos 10 dias)</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">IPs de origen y detalle diario de visitas de tipo <code>view_event</code>.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-slate-100 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">IP</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Visitas (10d)</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Ultima visita</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Detalle diario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($metrics['ip_activity_last_10_days'] as $row)
                        <tr class="border-t border-slate-200 dark:border-slate-700">
                            <td class="px-4 py-3 font-mono text-sm text-slate-800 dark:text-white">{{ $row->ip_address }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($row->visits_total) }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $row->last_day ? \Illuminate\Support\Carbon::parse($row->last_day)->format('d/m/Y') : '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                                @foreach($row->daily as $daily)
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 dark:bg-slate-700 px-2 py-1 mr-1 mb-1">{{ \Illuminate\Support\Carbon::parse($daily['day'])->format('d/m') }}: {{ $daily['total'] }}</span>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No hay visitas por IP registradas en los ultimos 10 dias.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
