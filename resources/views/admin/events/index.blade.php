@extends('layouts.admin')

@section('title', 'Eventos - Admin')

@section('admin')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Eventos</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Gestiona los eventos disponibles para reserva.</p>
    </div>
    <a href="{{ route('admin.events.create') }}" class="rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-5 py-2.5 text-white font-semibold shadow-lg shadow-violet-500/30 hover:shadow-violet-500/50 transition shrink-0">
        Nuevo evento
    </a>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px]">
            <thead class="bg-slate-100 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Nombre</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Fecha</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Lugar</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Butacas</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Activo</th>
                    <th class="text-right px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                    <tr class="border-t border-slate-200 dark:border-slate-700 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                        <td class="px-5 py-4 text-slate-800 dark:text-white font-medium">{{ $event->name }}</td>
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400">{{ $event->starts_at->translatedFormat('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400">{{ $event->venue }}</td>
                        <td class="px-5 py-4">
                            @if($event->venue_id)
                                <span class="inline-flex rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 px-3 py-1 text-sm font-medium">Con butacas</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400 px-3 py-1 text-sm font-medium">Sin butacas</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($event->is_active)
                                <span class="inline-flex rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 px-3 py-1 text-sm font-medium">Sí</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400 px-3 py-1 text-sm font-medium">No</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                @if($event->venue_id)
                                    <a href="{{ route('admin.events.seats', $event) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition">Ver butacas</a>
                                @endif
                                <a href="{{ route('admin.ticket-templates.edit', $event) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition">Ticket</a>
                                <a href="{{ route('admin.events.edit', $event) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition">Editar</a>
                                <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="inline" onsubmit="return confirm('¿Eliminar este evento?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 transition">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">No hay eventos. <a href="{{ route('admin.events.create') }}" class="text-violet-600 dark:text-violet-400 font-medium hover:underline">Crear uno</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
        {{ $events->links() }}
    </div>
</div>
@endsection
