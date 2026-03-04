@extends('layouts.admin')

@section('title', 'Usuarios - Admin')

@section('admin')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Usuarios registrados</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">Gestiona roles de administrador.</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px]">
            <thead class="bg-slate-100 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Nombre</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Email</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">CI / Teléfono</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Rol</th>
                    <th class="text-right px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr class="border-t border-slate-200 dark:border-slate-700 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                        <td class="px-5 py-4 font-medium text-slate-800 dark:text-white">{{ $u->name }}</td>
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400">{{ $u->email }}</td>
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400 text-sm">{{ $u->ci }} / {{ $u->phone }}</td>
                        <td class="px-5 py-4">
                            @if($u->role === 'admin')
                                <span class="inline-flex rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 px-3 py-1 text-sm font-medium">Admin</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400 px-3 py-1 text-sm font-medium">Usuario</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if($u->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.toggle-role', $u) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($u->role === 'admin')
                                        <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition">Quitar admin</button>
                                    @else
                                        <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition">Hacer admin</button>
                                    @endif
                                </form>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 text-sm">Tú</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">No hay usuarios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
        {{ $users->links() }}
    </div>
</div>
@endsection
