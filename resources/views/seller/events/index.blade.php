@extends('layouts.app')

@section('title', 'Vender tickets')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-white font-display tracking-wide">Vender tickets</h1>
    <p class="text-white/70 mt-2">Selecciona un evento para registrar una venta a nombre de un cliente.</p>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($events as $event)
        <div class="rounded-xl border border-red-900/50 bg-black/60 p-5 flex flex-col">
            <h2 class="text-lg font-semibold text-white">{{ $event->name }}</h2>
            <p class="text-sm text-white/60 mt-1">{{ $event->starts_at->translatedFormat('d M Y, H:i') }}</p>
            @if($event->venue)
                <p class="text-sm text-white/50 mt-1">{{ $event->venue }}</p>
            @endif
            <a href="{{ route('seller.events.surrogate-sale.create', $event) }}"
               class="mt-4 inline-flex justify-center rounded-lg bg-[#e50914] px-4 py-2 text-sm font-semibold text-white hover:bg-red-600 transition">
                Vender para cliente
            </a>
        </div>
    @empty
        <p class="text-white/60 col-span-full">No hay eventos con venta activa en este momento.</p>
    @endforelse
</div>
@endsection
