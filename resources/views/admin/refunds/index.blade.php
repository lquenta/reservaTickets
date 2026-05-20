@extends('layouts.admin')

@section('title', 'Reembolsos - Admin')

@section('admin')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Reembolsos manuales</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">Revisa el comprobante y el monto de la reserva antes de confirmar el reembolso.</p>
</div>

<form method="GET" action="{{ route('admin.refunds.index') }}" class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 mb-6 space-y-4">
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="event_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Evento <span class="text-red-500">*</span></label>
            <select id="event_id" name="event_id" required class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2"
                    onchange="if(this.value) this.form.submit()">
                <option value="">Seleccionar evento</option>
                @foreach($events as $ev)
                    <option value="{{ $ev->id }}" {{ (int) $eventId === $ev->id ? 'selected' : '' }}>{{ $ev->name }} ({{ $ev->starts_at->format('d/m/Y') }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="q" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Buscar</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Nombre, correo, código… o butaca (ej. B2, B-2)"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
        </div>
    </div>
    <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-700 text-white px-5 py-2.5 font-semibold">Buscar</button>
</form>

@if($selectedEvent && $reservations->isNotEmpty())
    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">{{ $reservations->total() }} reserva(s) confirmada(s) en <strong>{{ $selectedEvent->name }}</strong></p>

    <div class="space-y-6">
        @foreach($reservations as $reservation)
            @php
                $hasValidated = $reservation->hasValidatedTickets();
                $refundAmount = $reservation->sale_amount ?? app(\App\Services\ReservationPricingService::class)->totalForReservation($reservation);
            @endphp
            <article class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/80 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-bold text-slate-800 dark:text-white text-lg">{{ $reservation->user?->name }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $reservation->user?->email }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase text-slate-500 font-semibold">Código de pago</p>
                        <p class="font-mono font-semibold text-violet-700 dark:text-violet-300">{{ $reservation->payment_code }}</p>
                    </div>
                </div>

                <div class="p-5 grid lg:grid-cols-2 gap-6">
                    <div class="space-y-4 text-sm">
                        <h3 class="font-semibold text-slate-800 dark:text-white">Detalle de la reserva</h3>
                        <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-2 text-slate-600 dark:text-slate-400">
                            <dt class="font-medium text-slate-500">Evento</dt>
                            <dd class="text-slate-800 dark:text-slate-200">{{ $reservation->event?->name }}</dd>
                            <dt class="font-medium text-slate-500">Fecha evento</dt>
                            <dd>{{ $reservation->event?->starts_at?->translatedFormat('d/m/Y H:i') }}</dd>
                            <dt class="font-medium text-slate-500">Reserva creada</dt>
                            <dd>{{ $reservation->created_at->translatedFormat('d/m/Y H:i') }}</dd>
                            @if($reservation->confirmed_payment_at)
                                <dt class="font-medium text-slate-500">Pago confirmado</dt>
                                <dd>{{ $reservation->confirmed_payment_at->translatedFormat('d/m/Y H:i') }}</dd>
                            @endif
                            <dt class="font-medium text-slate-500">Tipo</dt>
                            <dd>
                                @if($reservation->sale_type === 'surrogate')
                                    Venta surrogada
                                    @if($reservation->soldBy)
                                        ({{ $reservation->soldBy->name }})
                                    @endif
                                @elseif($reservation->sale_type === 'honored_guest')
                                    Invitado de honor
                                @else
                                    Estándar
                                @endif
                            </dd>
                            <dt class="font-medium text-slate-500">Entradas</dt>
                            <dd>
                                <ul class="list-disc list-inside space-y-0.5">
                                    @foreach($reservation->reservationTickets as $ticket)
                                        <li>
                                            <span class="font-medium text-slate-800 dark:text-slate-200">{{ $ticket->holder_name }}</span>
                                            @if($ticket->seat)
                                                — {{ $ticket->seat->display_label }}
                                            @elseif($ticket->section)
                                                — {{ $ticket->section->name ?? 'Sección' }}
                                            @endif
                                            @if($ticket->validated_at)
                                                <span class="text-amber-600 dark:text-amber-400 text-xs">(validada en puerta)</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </dd>
                        </dl>
                    </div>

                    <div class="space-y-4">
                        <h3 class="font-semibold text-slate-800 dark:text-white">Comprobante de pago</h3>
                        @if($reservation->payment_receipt_path)
                            <a href="{{ asset('storage/'.$reservation->payment_receipt_path) }}" target="_blank" rel="noopener"
                               class="block rounded-xl border border-slate-200 dark:border-slate-600 overflow-hidden bg-slate-100 dark:bg-slate-900/50">
                                <img src="{{ asset('storage/'.$reservation->payment_receipt_path) }}" alt="Comprobante de pago"
                                     class="w-full max-h-80 object-contain">
                            </a>
                            <p class="text-xs text-slate-500">Clic en la imagen para abrir en tamaño completo.</p>
                        @elseif($reservation->isHonoredGuest())
                            <p class="rounded-xl border border-dashed border-slate-300 dark:border-slate-600 px-4 py-8 text-center text-slate-500 text-sm">
                                Invitado de honor — sin comprobante de pago.
                            </p>
                        @else
                            <p class="rounded-xl border border-dashed border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 px-4 py-6 text-center text-amber-800 dark:text-amber-200 text-sm">
                                No hay comprobante adjunto en esta reserva.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 bg-orange-50/60 dark:bg-orange-900/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase font-semibold text-orange-700 dark:text-orange-300">Monto a reembolsar</p>
                        <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($refundAmount, 2) }} <span class="text-lg font-semibold">Bs</span></p>
                        <p class="text-xs text-slate-500 mt-1">Reembolso de la reserva completa ({{ $reservation->reservationTickets->count() }} entrada(s)).</p>
                    </div>
                    <div class="sm:min-w-[240px]">
                        @if($hasValidated)
                            <p class="rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-4 py-3 text-sm font-medium text-center">
                                No reembolsable: entrada validada en puerta.
                            </p>
                        @else
                            <form method="POST" action="{{ route('admin.refunds.refund', $reservation) }}"
                                  onsubmit="return confirm('¿Confirmar reembolso de {{ number_format($refundAmount, 2) }} Bs para {{ $reservation->user?->name }}?');">
                                @csrf
                                <input type="hidden" name="redirect" value="{{ request()->fullUrl() }}">
                                <label for="refund_reason_{{ $reservation->id }}" class="sr-only">Motivo</label>
                                <input type="text" id="refund_reason_{{ $reservation->id }}" name="refund_reason" placeholder="Motivo del reembolso (opcional)"
                                       class="w-full mb-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 px-3 py-2 text-sm">
                                <button type="submit" class="w-full rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 transition">
                                    Confirmar reembolso
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-6">{{ $reservations->links() }}</div>
@elseif($selectedEvent)
    <p class="text-slate-500 dark:text-slate-400 rounded-xl border border-dashed border-slate-300 dark:border-slate-600 px-6 py-10 text-center">
        No hay reservas confirmadas que coincidan con la búsqueda.
    </p>
@endif
@endsection
