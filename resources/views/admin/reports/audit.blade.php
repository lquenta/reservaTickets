@extends('layouts.admin')

@section('title', 'Auditoría de reservas - Admin')

@section('admin')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Auditoría de reservas</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">Registro de intentos de reserva, creaciones, confirmaciones de pago y acciones de admin (autorizar/rechazar).</p>
</div>

{{-- Filtros --}}
<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 mb-6">
    <form method="GET" action="{{ route('admin.reports.audit') }}" class="flex flex-wrap gap-4 items-end">
        <div>
            <label for="action" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Acción</label>
            <select name="action" id="action" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2 min-w-[180px]">
                <option value="">Todas</option>
                @foreach($actionLabels as $value => $label)
                    <option value="{{ $value }}" {{ request('action') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="result" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Resultado</label>
            <select name="result" id="result" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2 min-w-[120px]">
                <option value="">Todos</option>
                <option value="success" {{ request('result') === 'success' ? 'selected' : '' }}>Éxito</option>
                <option value="failed" {{ request('result') === 'failed' ? 'selected' : '' }}>Fallido</option>
            </select>
        </div>
        <div>
            <label for="event_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Evento</label>
            <select name="event_id" id="event_id" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2 min-w-[200px]">
                <option value="">Todos</option>
                @foreach($events as $ev)
                    <option value="{{ $ev->id }}" {{ request('event_id') == $ev->id ? 'selected' : '' }}>{{ $ev->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="user_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Usuario</label>
            <select name="user_id" id="user_id" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2 min-w-[200px]">
                <option value="">Todos</option>
                @foreach($usersWithLogs as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date_from" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Desde</label>
            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                   class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2">
        </div>
        <div>
            <label for="date_to" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Hasta</label>
            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                   class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white px-3 py-2">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-700 px-4 py-2.5 text-white font-semibold transition">Filtrar</button>
            <a href="{{ route('admin.reports.audit') }}" class="rounded-xl border border-slate-300 dark:border-slate-600 px-4 py-2.5 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition">Limpiar</a>
        </div>
    </form>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
    <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-xl font-bold text-slate-800 dark:text-white">Registros</h2>
        <a href="{{ route('admin.reports.pdf.audit') . '?' . http_build_query(request()->query()) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2.5 text-white font-semibold transition">
            <span aria-hidden="true">📄</span> Descargar PDF
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[800px]">
            <thead class="bg-slate-100 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Fecha</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Acción</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Resultado</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Usuario</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Evento</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">IP</th>
                    <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Detalle</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr class="border-t border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-slate-800 dark:text-white whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $actionLabels[$log->action] ?? $log->action }}</td>
                        <td class="px-4 py-3">
                            @if($log->result === 'success')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/50 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:text-emerald-200">Éxito</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/50 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:text-red-200">Fallido</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                            @if($log->user)
                                {{ $log->user->name }}<br><span class="text-xs text-slate-500">{{ $log->user->email }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $log->event?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 font-mono text-sm">{{ $log->ip_address ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-sm max-w-xs truncate" title="{{ $log->message }}">{{ $log->message ? Str::limit($log->message, 40) : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No hay registros de auditoría con los filtros aplicados.</td>
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

<p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
    <a href="{{ route('admin.reports.index') }}" class="text-violet-600 dark:text-violet-400 hover:underline">← Volver a Reportes</a>
</p>
@endsection
