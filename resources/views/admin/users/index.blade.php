@extends('layouts.admin')



@section('title', 'Usuarios - Admin')



@section('admin')

<div class="mb-8">

    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Usuarios registrados</h1>

    <p class="text-slate-600 dark:text-slate-400 mt-1">Gestiona roles de administrador, vendedor y usuario.</p>

</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 mb-6 flex flex-wrap items-end gap-4">
    <div class="flex-1 min-w-[200px]">
        <label for="q" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Buscar usuario</label>
        <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Nombre, correo, CI, teléfono o ID"
               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2 text-slate-900 dark:text-white">
    </div>
    <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-700 text-white px-5 py-2.5 font-semibold">Buscar</button>
    @if(request()->filled('q'))
        <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-300 dark:border-slate-600 px-5 py-2.5 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition">Limpiar</a>
    @endif
</form>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">

    <div class="overflow-x-auto">

        <table class="w-full min-w-[640px]">

            <thead class="bg-slate-100 dark:bg-slate-700/50">

                <tr>

                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Nombre</th>

                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Email</th>

                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">CI / Teléfono</th>

                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Email / Origen</th>

                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Rol</th>

                    <th class="text-right px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Acciones</th>

                </tr>

            </thead>

            <tbody>

                @forelse($users as $u)

                    <tr class="border-t border-slate-200 dark:border-slate-700 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">

                        <td class="px-5 py-4 font-medium text-slate-800 dark:text-white">{{ $u->name }}</td>

                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400">{{ $u->displayEmail() ?? '— (invitado temporal)' }}</td>

                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400 text-sm">{{ $u->ci ?? '—' }} / {{ $u->phone }}</td>

                        <td class="px-5 py-4 text-sm">

                            @if($u->hasVerifiedEmail())

                                <span class="text-emerald-600 dark:text-emerald-400">Verificado</span>

                            @else

                                <span class="text-amber-600 dark:text-amber-400">Pendiente</span>

                            @endif

                            @if($u->createdBy)

                                <p class="text-xs text-slate-500 mt-1">Creado por {{ $u->createdBy->name }}</p>

                            @endif

                            @if($u->isGuest())

                                <span class="inline-flex mt-1 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 px-2 py-0.5 text-xs font-medium">Invitado temporal</span>

                            @endif

                            @if($u->provisioned_via)

                                <span class="inline-flex mt-1 rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 px-2 py-0.5 text-xs">{{ $u->provisioned_via }}</span>

                            @endif

                        </td>

                        <td class="px-5 py-4">

                            @if($u->isAdmin())

                                <span class="inline-flex rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 px-3 py-1 text-sm font-medium">Admin</span>

                            @elseif($u->isVendedor())

                                <span class="inline-flex rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-3 py-1 text-sm font-medium">Vendedor</span>

                            @else

                                <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400 px-3 py-1 text-sm font-medium">Usuario</span>

                            @endif

                        </td>

                        <td class="px-5 py-4 text-right">

                            @if($u->id !== auth()->id() && !$u->isAdmin() && !$u->isVendedor() && !$u->hasVerifiedEmail())

                                <form method="POST" action="{{ route('admin.users.verify-email', $u) }}" class="inline mr-1">

                                    @csrf

                                    @method('PATCH')

                                    <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition">Verificar email</button>

                                </form>

                            @endif

                            @if($u->id !== auth()->id())
                                <div class="inline-flex flex-wrap gap-1 justify-end">
                                    @if($u->isAdmin())
                                        <form method="POST" action="{{ route('admin.users.set-role', $u) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_USER }}">
                                            <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition">Quitar admin</button>
                                        </form>
                                    @elseif($u->isVendedor())
                                        <form method="POST" action="{{ route('admin.users.set-role', $u) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_USER }}">
                                            <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition">Quitar vendedor</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.set-role', $u) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_ADMIN }}">
                                            <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition">Hacer admin</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.set-role', $u) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_VENDEDOR }}">
                                            <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition">Hacer vendedor</button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 text-sm">Tú</span>
                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">
                            @if(request()->filled('q'))
                                No hay usuarios que coincidan con la búsqueda.
                            @else
                                No hay usuarios.
                            @endif
                        </td>

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

