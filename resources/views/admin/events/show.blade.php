@extends('layouts.admin')

@section('title', $event->name . ' - Admin')

@section('admin')
<nav class="mb-6 text-sm text-slate-600 dark:text-slate-400">
    <a href="{{ route('admin.events.index') }}" class="hover:text-violet-600 dark:hover:text-violet-400">← Eventos</a>
</nav>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">{{ $event->name }}</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-2">
        {{ $event->starts_at->translatedFormat('l d/m/Y H:i') }} · {{ $event->venue }}
    </p>
    <div class="flex flex-wrap gap-2 mt-3">
        @if(!$event->is_active)
            <span class="rounded-full bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 px-3 py-1 text-sm font-medium">Sold out</span>
        @elseif($event->sales_paused)
            <span class="rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 px-3 py-1 text-sm font-medium">Ventas pausadas</span>
        @else
            <span class="rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 px-3 py-1 text-sm font-medium">Ventas activas</span>
        @endif
    </div>
    @if($lastReschedule)
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
            Última reprogramación: {{ $lastReschedule->previous_starts_at->format('d/m/Y H:i') }} → {{ $lastReschedule->new_starts_at->format('d/m/Y H:i') }}
            ({{ $lastReschedule->created_at->diffForHumans() }})
        </p>
    @endif
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
    <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-4">
        <p class="text-xs font-semibold uppercase text-violet-600 dark:text-violet-400">Confirmadas</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $event->confirmed_count }}</p>
    </div>
    <div class="rounded-2xl border-2 border-amber-200/60 dark:border-amber-700/50 bg-white dark:bg-slate-800/80 p-4">
        <p class="text-xs font-semibold uppercase text-amber-600 dark:text-amber-400">Pendientes pago</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $event->pending_count }}</p>
    </div>
    <div class="rounded-2xl border-2 border-slate-200/60 dark:border-slate-600 bg-white dark:bg-slate-800/80 p-4">
        <p class="text-xs font-semibold uppercase text-slate-500">Reembolsadas</p>
        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $event->refunded_count }}</p>
    </div>
    @if($event->venue_id)
        <div class="rounded-2xl border-2 border-indigo-200/60 dark:border-indigo-700/50 bg-white dark:bg-slate-800/80 p-4">
            <p class="text-xs font-semibold uppercase text-indigo-600 dark:text-indigo-400">Butacas ocupadas</p>
            <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $occupiedSeats }}<span class="text-lg text-slate-500">/{{ $totalSeats }}</span></p>
        </div>
    @endif
</div>

<section id="ventas" class="mb-10">
    <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-4">Ventas</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @if($event->is_active && !$event->sales_paused)
            @include('admin.events._action-tile', [
                'href' => route('admin.events.surrogate-sale.create', $event),
                'icon' => '🛒',
                'title' => 'Venta surrogada',
                'description' => 'Registrar compra en nombre de un cliente.',
                'button' => 'Iniciar venta',
            ])
            @include('admin.events._action-tile', [
                'href' => route('admin.events.honored-guest.create', $event),
                'icon' => '⭐',
                'title' => 'Invitado de honor',
                'description' => 'Cortesía sin cobro.',
                'button' => 'Registrar invitado',
            ])
        @endif
        @include('admin.events._action-tile', [
            'href' => route('admin.refunds.index', ['event_id' => $event->id]),
            'icon' => '↩️',
            'title' => 'Reembolsos',
            'description' => 'Buscar reservas confirmadas y procesar devolución.',
            'button' => 'Gestionar reembolsos',
        ])
    </div>
</section>

<section id="operaciones" class="mb-10">
    <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-4">Operaciones</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @if($event->venue_id)
            @include('admin.events._action-tile', [
                'href' => route('admin.events.seats', $event),
                'icon' => '🪑',
                'title' => 'Mapa de butacas',
                'description' => 'Ver ocupación y bloquear butacas.',
                'button' => 'Ver mapa',
            ])
        @endif
        @if($event->is_active)
            @if($event->sales_paused)
                @include('admin.events._action-tile', [
                    'href' => route('admin.events.resume-sales', $event),
                    'icon' => '▶️',
                    'title' => 'Reanudar ventas',
                    'method' => 'PATCH',
                    'confirm' => '¿Reanudar ventas para este evento?',
                    'button' => 'Reanudar',
                ])
            @else
                @include('admin.events._action-tile', [
                    'href' => route('admin.events.pause-sales', $event),
                    'icon' => '⏸️',
                    'title' => 'Pausar ventas',
                    'description' => 'El evento sigue visible; no se pueden reservar.',
                    'method' => 'PATCH',
                    'confirm' => '¿Pausar ventas?',
                    'button' => 'Pausar',
                ])
            @endif
            @include('admin.events._action-tile', [
                'href' => route('admin.events.sold-out', $event),
                'icon' => '🚫',
                'title' => 'Sold out',
                'method' => 'PATCH',
                'confirm' => '¿Marcar como SOLD OUT?',
                'button' => 'Marcar sold out',
                'danger' => true,
            ])
        @else
            @include('admin.events._action-tile', [
                'href' => route('admin.events.reopen-sales', $event),
                'icon' => '🔓',
                'title' => 'Reabrir ventas',
                'method' => 'PATCH',
                'confirm' => '¿Reabrir ventas?',
                'button' => 'Reabrir',
            ])
        @endif
        @include('admin.events._action-tile', [
            'href' => route('admin.events.reschedule.create', $event),
            'icon' => '📅',
            'title' => 'Reprogramar fecha',
            'description' => 'Cambiar fecha/hora con registro en historial.',
            'button' => 'Reprogramar',
        ])
        @include('admin.events._action-tile', [
            'href' => route('admin.reservations.index', ['event_id' => $event->id]),
            'icon' => '📋',
            'title' => 'Reservas del evento',
            'button' => 'Ver reservas',
        ])
    </div>
</section>

<section id="configuracion" class="mb-10">
    <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-4">Configuración</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @include('admin.events._action-tile', [
            'href' => route('admin.events.edit', $event),
            'icon' => '✏️',
            'title' => 'Editar evento',
            'description' => 'Nombre, descripción, imágenes, secciones.',
            'button' => 'Editar',
        ])
        @include('admin.events._action-tile', [
            'href' => route('admin.ticket-templates.edit', $event),
            'icon' => '🎟️',
            'title' => 'Plantilla de ticket',
            'button' => 'Configurar',
        ])
        @include('admin.events._action-tile', [
            'href' => route('admin.reports.index', ['tab' => 'nombres-por-evento', 'event_id' => $event->id]),
            'icon' => '📈',
            'title' => 'Reportes',
            'description' => 'Nombres por evento y reembolsos.',
            'button' => 'Ver reportes',
        ])
    </div>
</section>

<section x-data="{ open: false }" class="rounded-2xl border-2 border-red-300/60 dark:border-red-800/50 bg-red-50/30 dark:bg-red-900/10 p-6">
    <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left font-semibold text-red-700 dark:text-red-300">
        Zona peligro
        <span x-text="open ? '−' : '+'"></span>
    </button>
    <div x-show="open" x-cloak class="mt-4">
        @include('admin.events._action-tile', [
            'href' => route('admin.events.destroy', $event),
            'icon' => '🗑️',
            'title' => 'Eliminar evento',
            'description' => 'Acción irreversible.',
            'method' => 'DELETE',
            'confirm' => '¿Eliminar este evento?',
            'button' => 'Eliminar',
            'danger' => true,
        ])
    </div>
</section>
@endsection
