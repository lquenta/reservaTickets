@extends('layouts.admin')

@section('title', 'Lugares - Admin')

@section('admin')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Lugares / Salas</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Gestiona los venues para reserva por butaca. Cada lugar tiene su plano y butacas.</p>
    </div>
    <a href="{{ route('admin.venues.create') }}" class="rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-5 py-2.5 text-white font-semibold shadow-lg shadow-violet-500/30 hover:shadow-violet-500/50 transition shrink-0">
        Nuevo lugar
    </a>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px]">
            <thead class="bg-slate-100 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Nombre</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Filas × Columnas</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Butacas</th>
                    <th class="text-right px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($venues as $venue)
                    <tr class="border-t border-slate-200 dark:border-slate-700 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                        <td class="px-5 py-4 text-slate-800 dark:text-white font-medium">{{ $venue->name }}</td>
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400">{{ $venue->seat_rows }} × {{ $venue->seat_columns }}</td>
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400">{{ $venue->seats_count }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.venues.edit', $venue) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition">Editar</a>
                            <form method="POST" action="{{ route('admin.venues.destroy', $venue) }}" class="inline" onsubmit="return confirm('¿Eliminar este lugar?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 transition">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">No hay lugares. <a href="{{ route('admin.venues.create') }}" class="text-violet-600 dark:text-violet-400 font-medium hover:underline">Crear uno</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
        {{ $venues->links() }}
    </div>
</div>
@endsection
