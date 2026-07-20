@extends('layouts.admin')

@section('title', 'Butacas ocupadas - ' . $event->name)

@section('admin')
@include('shared.layout-map-scripts')
@include('admin.events._seats-map-alpine')

<div class="mb-8">
    <a href="{{ route('admin.events.index') }}" class="inline-flex items-center gap-2 text-white/70 hover:text-[#e11d8a] font-medium transition mb-4">
        &larr; Volver a eventos
    </a>
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-[#e11d8a] tracking-widest">Butacas del evento</h1>
    <p class="text-lg text-white/80 mt-1">{{ $event->name }} — {{ $event->starts_at->translatedFormat('d/m/Y H:i') }}</p>
</div>

@include('shared.event-seats-overview-map', ['readonly' => true])
@endsection
