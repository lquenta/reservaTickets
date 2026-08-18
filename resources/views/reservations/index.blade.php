@extends('layouts.app')

@section('title', 'Mis reservas')

@section('content')
<div class="mb-10">
    <h1 class="font-display text-4xl font-bold text-[#ff2daa] tracking-widest mb-2">MIS RESERVAS</h1>
    <p class="text-white/80">Revisa el estado de tus reservas y continúa el checkout si aún está en proceso.</p>
</div>

@if($reservations->isEmpty())
    <div class="rounded-2xl border border-purple-900/50 bg-black/60 backdrop-blur p-16 text-center">
        <x-icon name="ticket" class="w-14 h-14 mx-auto mb-4 text-[#ff2daa] opacity-80" />
        <p class="text-white/80 text-lg">No tienes reservas.</p>
        <a href="{{ route('events.index') }}" class="inline-block mt-6 rounded-xl bg-[#ff2daa] px-6 py-3 text-white font-semibold hover:bg-pink-600 transition">Ver eventos</a>
    </div>
@else
    <div class="space-y-6">
        @foreach($reservations as $r)
            <div class="rounded-2xl border border-purple-900/50 bg-black/60 backdrop-blur p-6 md:p-8 flex flex-wrap justify-between items-center gap-6 hover:border-[#ff2daa]/40 transition">
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-white">{{ $r->event->name }}</h2>
                    <p class="text-white/70 mt-1">{{ $r->event->starts_at->translatedFormat('d/m/Y H:i') }} · {{ $r->reservationTickets->count() }} ticket(s)</p>
                    <p class="mt-2">
                        @if($r->status === 'INICIADO')
                            <span class="inline-flex items-center rounded-full bg-amber-900/50 text-amber-200 px-3 py-1 text-sm font-medium">En proceso</span>
                        @elseif($r->status === 'PENDIENTE_PAGO')
                            <span class="inline-flex items-center rounded-full bg-blue-900/50 text-blue-200 px-3 py-1 text-sm font-medium">Pendiente de pago</span>
                        @elseif($r->status === 'CONFIRMADO')
                            <span class="inline-flex items-center rounded-full bg-emerald-900/50 text-emerald-200 px-3 py-1 text-sm font-medium">Confirmado</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-white/10 text-white/60 px-3 py-1 text-sm font-medium">Cancelado</span>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    @if($r->status === 'CONFIRMADO')
                        <a href="{{ route('reservations.tickets-pdf', ['reservation' => $r, 'download' => 1]) }}" class="inline-flex items-center gap-2 rounded-xl bg-[#ff2daa] px-5 py-2.5 text-white font-semibold hover:bg-pink-600 transition">
                            <x-icon name="download" class="w-4 h-4" /> Descargar mis tickets
                        </a>
                    @endif
                    @if($r->status === 'INICIADO' && !$r->isExpired())
                        <a href="{{ route('checkout.show', $r) }}" class="rounded-xl bg-white/10 hover:bg-white/20 px-5 py-2.5 text-white font-semibold border border-white/30 transition shrink-0">
                            Continuar checkout
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-8 [&_.bg-white]:bg-black/60 [&_.text-slate-700]:text-white [&_a]:text-[#ff2daa] [&_a:hover]:text-[#a569ff]">{{ $reservations->links() }}</div>
@endif
@endsection
