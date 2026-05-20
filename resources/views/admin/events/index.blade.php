@extends('layouts.admin')

@section('title', 'Eventos - Admin')

@section('admin')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Eventos</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Catálogo de eventos. Abre un evento para gestionar ventas, butacas y operaciones.</p>
    </div>
    <a href="{{ route('admin.events.create') }}" class="rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-5 py-2.5 text-white font-semibold shadow-lg shadow-violet-500/30 hover:shadow-violet-500/50 transition shrink-0">
        Nuevo evento
    </a>
</div>

@php
    $filters = [
        'all' => 'Todos',
        'active' => 'Activos',
        'past' => 'Pasados',
        'paused' => 'Ventas pausadas',
    ];
@endphp
<div class="flex flex-wrap gap-2 mb-6">
    @foreach($filters as $key => $label)
        <a href="{{ route('admin.events.index', ['filter' => $key]) }}"
           class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ ($filter ?? 'all') === $key ? 'bg-violet-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if($events->isEmpty())
    <div class="rounded-2xl border-2 border-dashed border-violet-300 dark:border-violet-700 p-12 text-center text-slate-500 dark:text-slate-400">
        No hay eventos con este filtro.
        <a href="{{ route('admin.events.create') }}" class="block mt-2 text-violet-600 dark:text-violet-400 font-medium hover:underline">Crear uno</a>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($events as $event)
            @include('admin.events._card', ['event' => $event])
        @endforeach
    </div>
    <div class="mt-8">
        {{ $events->links() }}
    </div>
@endif
@endsection
