@extends('layouts.app')

@section('title', 'Vender tickets')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-white font-display tracking-wide">Vender tickets</h1>
    <p class="text-white/70 mt-2">Selecciona un evento para registrar una venta a nombre de un cliente.</p>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($events as $event)
        <div class="rounded-xl border border-fuchsia-900/50 bg-black/60 p-5 flex flex-col">
            <h2 class="text-lg font-semibold text-white">{{ $event->name }}</h2>
            <p class="text-sm text-white/60 mt-1">{{ $event->starts_at->translatedFormat('d M Y, H:i') }}</p>
            @if($event->venue)
                <p class="text-sm text-white/50 mt-1">{{ $event->venue }}</p>
            @endif
            <div class="mt-4 flex flex-col gap-2">
                <a href="{{ route('seller.events.surrogate-sale.create', $event) }}"
                   class="inline-flex justify-center rounded-lg bg-[#e11d8a] px-4 py-2 text-sm font-semibold text-white hover:bg-fuchsia-700 transition">
                    Vender para cliente
                </a>
                @if($event->venue_id)
                    <a href="{{ route('seller.events.seats', $event) }}"
                       class="inline-flex justify-center rounded-lg border border-fuchsia-900/60 bg-black/40 px-4 py-2 text-sm font-medium text-white/90 hover:border-[#e11d8a] hover:text-[#e11d8a] transition">
                        Ver mapa de butacas
                    </a>
                @endif
            </div>
        </div>
    @empty
        <p class="text-white/60 col-span-full">No hay eventos con venta activa en este momento.</p>
    @endforelse
</div>
@endsection
