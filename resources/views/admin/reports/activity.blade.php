@extends('layouts.admin')

@section('title', 'Actividad de usuarios - Admin')

@section('admin')
@php
    use App\Support\ActivityDuration;
    use App\Support\ActivityRouteLabels;
@endphp
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Actividad de usuarios</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1 max-w-3xl">Páginas vistas, clics y tiempo con la pestaña visible. No incluye lo que se escribe en los formularios. Se conserva 30 días y empieza a contarse desde que esta medición está activa.</p>
    </div>
    <a href="{{ route('admin.reports.index') }}" class="text-sm font-semibold text-violet-700 dark:text-violet-300 hover:underline">Volver a reportes</a>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 mb-6">
    <form method="GET" action="{{ route('admin.reports.activity') }}" class="flex flex-wrap gap-4 items-end">
        <div>
            <label for="q" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Persona</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] }}" placeholder="Nombre, correo o invitado"
                   class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2 min-w-[220px]">
        </div>
        <div>
            <label for="path" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Página</label>
            <input type="search" name="path" id="path" value="{{ $filters['path'] }}" placeholder="/events"
                   class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2 min-w-[180px]">
        </div>
        <div>
            <label for="kind" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Detalle</label>
            <select name="kind" id="kind" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2">
                <option value="all" @selected($filters['kind'] === 'all')>Páginas y acciones</option>
                <option value="pages" @selected($filters['kind'] === 'pages')>Solo páginas</option>
                <option value="actions" @selected($filters['kind'] === 'actions')>Solo acciones</option>
            </select>
        </div>
        <div>
            <label for="date_from" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Desde</label>
            <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from']->toDateString() }}"
                   class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2">
        </div>
        <div>
            <label for="date_to" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Hasta</label>
            <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to']->toDateString() }}"
                   class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-700 px-4 py-2.5 text-white font-semibold transition">Filtrar</button>
            <a href="{{ route('admin.reports.activity') }}" class="rounded-xl border border-slate-300 dark:border-slate-600 px-4 py-2.5 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition">Limpiar</a>
        </div>
    </form>
    @if($filters['clamped'])
        <p class="mt-3 text-sm text-amber-700 dark:text-amber-300">El rango se acotó a 31 días para que la consulta siga siendo liviana.</p>
    @endif
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 p-5">
        <p class="text-sm text-slate-500 dark:text-slate-400">Páginas vistas</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ number_format($summary['page_views'], 0, ',', '.') }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 p-5">
        <p class="text-sm text-slate-500 dark:text-slate-400">Acciones</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ number_format($summary['actions'], 0, ',', '.') }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 p-5">
        <p class="text-sm text-slate-500 dark:text-slate-400">Cuentas · visitas</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ number_format($summary['users'], 0, ',', '.') }} · {{ number_format($summary['sessions'], 0, ',', '.') }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 p-5">
        <p class="text-sm text-slate-500 dark:text-slate-400">Tiempo medio por página</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ ActivityDuration::format($summary['avg_ms']) }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Páginas más vistas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-100 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Página</th>
                        <th class="text-right px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Vistas</th>
                        <th class="text-right px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Tiempo medio</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topPages as $page)
                        <tr class="border-t border-slate-200 dark:border-slate-700">
                            <td class="px-4 py-3 text-slate-800 dark:text-white">
                                {{ ActivityRouteLabels::page($page->route_name, $page->path) }}
                                @if($page->route_name && ActivityRouteLabels::page($page->route_name, $page->path) !== $page->path)
                                    <div class="text-xs text-slate-500 font-mono">{{ $page->path }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">{{ $page->views }}</td>
                            <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">{{ ActivityDuration::format($page->avg_ms !== null ? (int) round((float) $page->avg_ms) : null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-slate-500">Todavía no hay páginas en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Por persona</h2>
            <p class="text-xs text-slate-500 mt-1">El tiempo es la suma de pestañas visibles. Dos pestañas abiertas a la vez se suman.</p>
        </div>
        <div class="overflow-x-auto max-h-[28rem]">
            <table class="w-full">
                <thead class="bg-slate-100 dark:bg-slate-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Persona</th>
                        <th class="text-right px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Páginas</th>
                        <th class="text-right px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Acciones</th>
                        <th class="text-right px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Tiempo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($actors as $actor)
                        <tr class="border-t border-slate-200 dark:border-slate-700">
                            <td class="px-4 py-3 text-slate-800 dark:text-white">
                                {{ $actor->name }}
                                @if($actor->email)
                                    <div class="text-xs text-slate-500">{{ $actor->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">{{ $actor->pages }}</td>
                            <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300">{{ $actor->actions }}</td>
                            <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ ActivityDuration::format($actor->visible_ms) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">Sin actividad en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
    <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-white">Detalle</h2>
            <p class="text-sm text-slate-500 mt-1">El resumen de arriba cubre el período completo. Esta tabla respeta el filtro de tipo.</p>
        </div>
        <a href="{{ route('admin.reports.pdf.activity') . '?' . http_build_query(request()->query()) }}" class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2.5 text-white font-semibold transition">
            <x-icon name="pdf" class="w-4 h-4" /> Descargar PDF
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[860px]">
            <thead class="bg-slate-100 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Cuándo</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Persona</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Qué hizo</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Dónde</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Tiempo</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Dispositivo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr class="border-t border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-slate-800 dark:text-white whitespace-nowrap">{{ $log->occurred_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                            @if($log->user)
                                {{ $log->user->name }}
                                <div class="text-xs text-slate-500">{{ $log->user->email }}</div>
                            @else
                                Invitado
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-800 dark:text-white">
                            {{ $log->actionLabel() }}
                            @if($log->label)
                                <span class="text-slate-600 dark:text-slate-300">· {{ $log->label }}</span>
                            @endif
                            @if($log->trackedEvent)
                                <div class="text-xs text-slate-500">{{ $log->trackedEvent->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                            {{ ActivityRouteLabels::page($log->route_name, $log->path) }}
                            @if($log->path)
                                <div class="text-xs font-mono text-slate-500">{{ $log->path }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                            {{ $log->event_name === \App\Models\AnalyticsEvent::EVENT_PAGE_VIEW ? ActivityDuration::format($log->duration_ms) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ $log->deviceLabel() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No hay actividad con estos filtros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-700">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
