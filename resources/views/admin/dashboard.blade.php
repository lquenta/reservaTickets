@extends('layouts.admin')

@section('title', 'Panel de administración')

@section('admin')
<div class="mb-10">
    <h1 class="text-4xl font-bold text-slate-800 dark:text-white mb-2">Panel de administración</h1>
    <p class="text-slate-600 dark:text-slate-400">Gestiona eventos, reservas y usuarios desde aquí.</p>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <a href="{{ route('admin.events.index') }}" class="group rounded-3xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-8 shadow-lg hover:shadow-xl hover:shadow-violet-500/10 hover:border-violet-400 dark:hover:border-violet-500 transition">
        <div class="text-4xl mb-4 group-hover:scale-110 transition">🎫</div>
        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Eventos</h2>
        <p class="text-slate-600 dark:text-slate-400 text-sm">Crear y editar eventos, portadas y códigos QR.</p>
    </a>
    <a href="{{ route('admin.reservations.index') }}" class="group rounded-3xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-8 shadow-lg hover:shadow-xl hover:shadow-violet-500/10 hover:border-violet-400 dark:hover:border-violet-500 transition">
        <div class="text-4xl mb-4 group-hover:scale-110 transition">📋</div>
        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Reservas</h2>
        <p class="text-slate-600 dark:text-slate-400 text-sm">Ver reservas y autorizar envío de tickets.</p>
    </a>
    <a href="{{ route('admin.users.index') }}" class="group rounded-3xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-8 shadow-lg hover:shadow-xl hover:shadow-violet-500/10 hover:border-violet-400 dark:hover:border-violet-500 transition">
        <div class="text-4xl mb-4 group-hover:scale-110 transition">👥</div>
        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Usuarios</h2>
        <p class="text-slate-600 dark:text-slate-400 text-sm">Ver usuarios y gestionar administradores.</p>
    </a>
</div>
@endsection
