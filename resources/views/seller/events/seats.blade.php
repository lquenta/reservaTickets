@extends('layouts.app')

@section('title', 'Mapa de butacas — ' . $event->name)

@section('content')
@include('shared.layout-map-scripts')
@include('admin.events._seats-map-alpine')

<nav class="sticky top-20 z-50 -mx-4 mb-6 flex items-center border-b border-red-900/40 bg-black/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
     aria-label="Navegación del mapa de butacas">
    <a href="{{ $backUrl ?? route('seller.events.index') }}"
       class="relative z-50 inline-flex items-center gap-2 rounded-lg border border-red-800/60 bg-black/80 px-4 py-2 text-sm font-semibold text-[#e50914] transition hover:bg-red-950/50 hover:text-red-400 pointer-events-auto">
        <span aria-hidden="true">←</span> Volver a eventos
    </a>
</nav>

<div class="relative z-0 mb-8">
    <h1 class="text-3xl font-bold text-white font-display tracking-wide">Mapa de butacas</h1>
    <p class="text-white/70 mt-2">{{ $event->name }} — {{ $event->starts_at->translatedFormat('d M Y, H:i') }}</p>
    @if($event->venue)
        <p class="text-sm text-white/50 mt-1">{{ $event->venue }}</p>
    @endif
</div>

<div class="relative z-0 isolate">
    @include('shared.event-seats-overview-map', ['readonly' => true])
</div>
@endsection
