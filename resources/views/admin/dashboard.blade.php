@extends('layouts.admin')

@section('title', 'Dashboard de métricas')

@section('admin')
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
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ventas</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['sales_total'], 2) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-violet-200/60 dark:border-violet-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Asistencia confirmada</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['attendance_confirmed']) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-violet-200/60 dark:border-violet-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Publico confirmado</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['confirmed_audience']) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800/80 border-2 border-violet-200/60 dark:border-violet-700/50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Reservado / pendiente</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ number_format($metrics['kpis']['reserved_pending']) }}</p>
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

    <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6">
        <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Tendencia diaria</h2>
        @if($metrics['trend']->isNotEmpty())
            <div class="space-y-2">
                @php $maxVisits = max(1, (int) $metrics['trend']->max('visits')); @endphp
                @foreach($metrics['trend'] as $row)
                    <div class="grid grid-cols-[95px_1fr_auto] gap-3 items-center text-sm">
                        <span class="text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Carbon::parse($row['day'])->format('d/m') }}</span>
                        <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                            <div class="h-full bg-violet-500" style="width: {{ min(100, ($row['visits'] / $maxVisits) * 100) }}%"></div>
                        </div>
                        <span class="text-slate-700 dark:text-slate-200">{{ $row['visits'] }} vis / {{ $row['conversions'] }} conv / {{ number_format($row['sales'], 2) }} Bs</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-slate-500 dark:text-slate-400">Sin datos de tendencia para el rango elegido.</p>
        @endif
    </div>

    <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Resumen por evento</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[840px]">
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
</div>
@endsection
