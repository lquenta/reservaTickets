@extends('layouts.admin')

@section('title', 'Reportes - Admin')

@section('admin')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Reportes</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Entradas vendidas, clientes y ventas (reservas confirmadas).</p>
    </div>
    <a href="{{ route('admin.reports.audit') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-violet-500 bg-violet-50 dark:bg-violet-900/30 dark:border-violet-600 px-4 py-2.5 text-violet-700 dark:text-violet-200 font-semibold hover:bg-violet-100 dark:hover:bg-violet-900/50 transition">
        <span aria-hidden="true">📋</span> Auditoría de reservas
    </a>
</div>

<div x-data="{ tab: @js(request('tab', 'entradas')) }" class="space-y-6">
    {{-- Tabs --}}
    <div class="flex flex-wrap gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
        <button type="button"
                @click="tab = 'entradas'"
                :class="tab === 'entradas' ? 'bg-violet-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                class="rounded-xl px-4 py-2.5 font-semibold transition">
            🎫 Entradas vendidas
        </button>
        <button type="button"
                @click="tab = 'clientes'"
                :class="tab === 'clientes' ? 'bg-violet-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                class="rounded-xl px-4 py-2.5 font-semibold transition">
            👥 Clientes que compraron
        </button>
        <button type="button"
                @click="tab = 'ventas'"
                :class="tab === 'ventas' ? 'bg-violet-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                class="rounded-xl px-4 py-2.5 font-semibold transition">
            📈 Reporte de ventas
        </button>
        <button type="button"
                @click="tab = 'clientes-por-evento'"
                :class="tab === 'clientes-por-evento' ? 'bg-violet-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                class="rounded-xl px-4 py-2.5 font-semibold transition">
            📋 Clientes por evento
        </button>
        <button type="button"
                @click="tab = 'nombres-por-evento'"
                :class="tab === 'nombres-por-evento' ? 'bg-violet-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                class="rounded-xl px-4 py-2.5 font-semibold transition">
            📝 Nombres por evento
        </button>
    </div>

    {{-- Reporte: Entradas vendidas --}}
    <div x-show="tab === 'entradas'" x-transition class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">Entradas vendidas</h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Total de tickets emitidos (reservas confirmadas).</p>
            </div>
            <a href="{{ route('admin.reports.pdf.entradas') }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2.5 text-white font-semibold transition">
                <span aria-hidden="true">📄</span> Descargar PDF
            </a>
        </div>
        <div class="p-6">
            <div class="rounded-xl bg-violet-100 dark:bg-violet-900/40 text-violet-800 dark:text-violet-200 p-6 mb-6 inline-block">
                <p class="text-sm font-semibold uppercase tracking-wide text-violet-600 dark:text-violet-400">Total entradas vendidas</p>
                <p class="text-4xl font-bold mt-1">{{ number_format($ticketsSoldTotal) }}</p>
            </div>
            @if($ticketsSoldByEvent->isNotEmpty())
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Por evento</p>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[320px]">
                        <thead class="bg-slate-100 dark:bg-slate-700/50">
                            <tr>
                                <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Evento</th>
                                <th class="text-right px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Entradas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ticketsSoldByEvent as $eventId => $row)
                                <tr class="border-t border-slate-200 dark:border-slate-700">
                                    <td class="px-4 py-3 text-slate-800 dark:text-white">{{ $eventsForTickets->get($eventId)?->name ?? 'Evento #'.$eventId }}</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ number_format($row->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400">Aún no hay entradas vendidas (ninguna reserva confirmada).</p>
            @endif
        </div>
    </div>

    {{-- Reporte: Clientes que compraron --}}
    <div x-show="tab === 'clientes'" x-transition class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">Clientes que ya compraron entrada</h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Listado de usuarios con al menos una reserva confirmada.</p>
            </div>
            <a href="{{ route('admin.reports.pdf.clientes') }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2.5 text-white font-semibold transition">
                <span aria-hidden="true">📄</span> Descargar PDF
            </a>
        </div>
        <div class="p-6">
            @if($clientsWithTickets->isNotEmpty())
                <div class="space-y-4">
                    @foreach($clientsWithTickets as $user)
                        <div class="rounded-xl border-2 border-slate-200 dark:border-slate-700 p-4 hover:border-violet-300 dark:hover:border-violet-600 transition">
                            <p class="font-bold text-slate-800 dark:text-white">{{ $user->name }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $user->email }} · {{ $user->phone ?? '—' }}</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-300">
                                @foreach($user->reservations as $res)
                                    <li class="flex items-center gap-2">
                                        <span class="text-violet-600 dark:text-violet-400 font-medium">{{ $res->event->name }}</span>
                                        <span>— {{ $res->reservationTickets->count() }} ticket(s)</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400">Ningún cliente ha completado una compra confirmada aún.</p>
            @endif
        </div>
    </div>

    {{-- Reporte: Clientes por evento --}}
    <div x-show="tab === 'clientes-por-evento'" x-transition class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">Clientes por evento</h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Lista de clientes que reservaron y confirmaron el ticket, agrupada por evento.</p>
            </div>
            <a href="{{ route('admin.reports.pdf.clientes-por-evento') }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2.5 text-white font-semibold transition">
                <span aria-hidden="true">📄</span> Descargar PDF
            </a>
        </div>
        <div class="p-6">
            @if($eventsWithClients->isNotEmpty())
                <div class="space-y-8">
                    @foreach($eventsWithClients as $event)
                        <div class="rounded-xl border-2 border-violet-200/60 dark:border-violet-700/50 overflow-hidden">
                            <div class="px-4 py-3 bg-violet-100 dark:bg-violet-900/40">
                                <h3 class="font-bold text-violet-800 dark:text-violet-200">{{ $event->name }}</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $event->starts_at->translatedFormat('d/m/Y H:i') }} · {{ $event->venue }}</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[400px]">
                                    <thead class="bg-slate-100 dark:bg-slate-700/50">
                                        <tr>
                                            <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Cliente</th>
                                            <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Email / Teléfono</th>
                                            <th class="text-right px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Tickets</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($event->reservations as $res)
                                            <tr class="border-t border-slate-200 dark:border-slate-700">
                                                <td class="px-4 py-3 font-medium text-slate-800 dark:text-white">{{ $res->user->name }}</td>
                                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ $res->user->email }} · {{ $res->user->phone ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right font-medium">{{ $res->reservationTickets->count() }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400">No hay eventos con reservas confirmadas.</p>
            @endif
        </div>
    </div>

    {{-- Reporte: Nombres por evento --}}
    <div x-show="tab === 'nombres-por-evento'" x-transition class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">Nombres por evento</h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Titulares y butaca asignada (reservas confirmadas) para un evento vigente.</p>
            </div>
        </div>
        <div class="p-6">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3 mb-5">
                <input type="hidden" name="tab" value="nombres-por-evento">
                <div class="min-w-[260px]">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Evento vigente</label>
                    <select name="event_id" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white focus:border-violet-500 focus:ring-violet-500" onchange="this.form.submit()">
                        @forelse($vigenteEvents as $ev)
                            <option value="{{ $ev->id }}" @selected((int)$selectedEventId === (int)$ev->id)>
                                {{ $ev->name }} — {{ $ev->starts_at?->translatedFormat('d/m/Y H:i') ?? '—' }}
                            </option>
                        @empty
                            <option value="">No hay eventos vigentes</option>
                        @endforelse
                    </select>
                </div>

                <div class="ml-auto">
                    <a href="{{ $selectedEventId ? route('admin.reports.pdf.nombres-por-evento', ['event_id' => $selectedEventId]) : '#' }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2.5 text-white font-semibold transition {{ $selectedEventId ? '' : 'pointer-events-none opacity-50' }}">
                        <span aria-hidden="true">📄</span> Descargar PDF
                    </a>
                </div>
            </form>

            @if($selectedEvent)
                <div class="mb-4 rounded-xl bg-violet-50 dark:bg-violet-900/30 border border-violet-200/60 dark:border-violet-700/50 p-4">
                    <p class="font-semibold text-violet-800 dark:text-violet-200">
                        {{ $selectedEvent->name }} — {{ $selectedEvent->starts_at?->translatedFormat('d/m/Y H:i') ?? '—' }}
                    </p>
                </div>
            @endif

            @if($reservationsForSelectedEvent->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px]">
                        <thead class="bg-slate-100 dark:bg-slate-700/50">
                            <tr>
                                <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Reserva</th>
                                <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Nombre completo</th>
                                <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Butaca</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservationsForSelectedEvent as $res)
                                @foreach($res->reservationTickets as $t)
                                    <tr class="border-t border-slate-200 dark:border-slate-700">
                                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ $res->payment_code ?? ('#'.$res->id) }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-800 dark:text-white">{{ $t->holder_name ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $t->seat?->display_label ?? 'Sin butaca' }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400">No hay reservas confirmadas para el evento seleccionado.</p>
            @endif
        </div>
    </div>

    {{-- Reporte de ventas --}}
    <div x-show="tab === 'ventas'" x-transition class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">Reporte de ventas</h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Entradas vendidas por evento y monto (precio unitario del ticket × cantidad).</p>
            </div>
            <a href="{{ route('admin.reports.pdf.ventas') }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2.5 text-white font-semibold transition">
                <span aria-hidden="true">📄</span> Descargar PDF
            </a>
        </div>
        <div class="p-6">
            @if($salesByEvent->isNotEmpty())
                <div class="rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200 p-6 mb-6 inline-block">
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Total ventas</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($salesTotal, 2) }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[400px]">
                        <thead class="bg-slate-100 dark:bg-slate-700/50">
                            <tr>
                                <th class="text-left px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Evento</th>
                                <th class="text-right px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Entradas</th>
                                <th class="text-right px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Precio unit.</th>
                                <th class="text-right px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesByEvent as $row)
                                <tr class="border-t border-slate-200 dark:border-slate-700">
                                    <td class="px-4 py-3 text-slate-800 dark:text-white">{{ $row->event_name }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($row->tickets_sold) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($row->unit_price, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ number_format($row->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400">Aún no hay ventas (reservas confirmadas) para mostrar.</p>
            @endif
        </div>
    </div>
</div>
@endsection
