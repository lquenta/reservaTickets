@extends('layouts.app')

@section('title', 'Eventos')

@section('content')
<div class="mb-10">
    <h1 class="font-display text-4xl md:text-5xl font-bold text-[#e50914] tracking-widest mb-2">EVENTOS DISPONIBLES</h1>
    <p class="text-white/80 text-lg">Encuentra tu próximo evento y reserva tus tickets.</p>
</div>

@if($events->isEmpty())
    <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-16 text-center">
        <div class="text-6xl mb-4 opacity-50">🎫</div>
        <p class="text-white/80 text-lg">No hay eventos disponibles en este momento.</p>
        <p class="text-white/50 text-sm mt-2">Vuelve pronto para ver nuevas fechas.</p>
    </div>
@else
    <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">
        @foreach($events as $event)
            <article class="group relative overflow-hidden rounded-2xl border border-red-900/50 hover:border-[#e50914]/50 transition-all duration-300 hover:-translate-y-1 min-h-[320px] flex flex-col">
                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-500 group-hover:scale-105
                     @if(!$event->cover_image_path) bg-gradient-to-br from-[#1a0505] to-[#e50914]/20 @endif"
                     @if($event->cover_image_path) style="background-image: url('{{ asset('storage/'.$event->cover_image_path) }}');" @endif>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>

                <div class="relative flex flex-col flex-1 p-6 justify-end">
                    <h2 class="text-2xl font-bold text-white mb-1 drop-shadow-lg">{{ $event->name }}</h2>
                    <p class="text-white/90 text-sm mb-1 flex items-center gap-1">
                        <span aria-hidden="true">📅</span>
                        {{ $event->starts_at->translatedFormat('l d F Y, H:i') }}
                    </p>
                    <p class="text-white/80 text-sm mb-5 flex items-center gap-1">
                        <span aria-hidden="true">📍</span>
                        {{ $event->venue }}
                    </p>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 text-white font-medium px-5 py-3 border border-white/30 hover:bg-white/20 transition w-fit backdrop-blur">
                                Panel de administración
                            </a>
                        @else
                            @if($event->venue_id)
                                <p class="text-white/60 text-xs mb-2">Reserva con elección de butaca en el checkout.</p>
                            @endif
                            <a href="{{ route('reservations.create', $event) }}" class="inline-flex items-center justify-center rounded-xl bg-[#e50914] text-white font-semibold px-5 py-3 hover:bg-red-600 transition w-fit">
                                Reservar tickets
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 text-white font-medium px-5 py-3 border border-white/30 hover:bg-white/20 transition w-fit backdrop-blur">
                            Inicia sesión para reservar
                        </a>
                    @endauth
                </div>
            </article>
        @endforeach
    </div>
    @if($events->hasPages())
        <div class="mt-10 flex justify-center [&_.bg-white]:bg-black/60 [&_.text-slate-700]:text-white [&_a]:text-[#e50914] [&_a:hover]:text-red-400">{{ $events->links() }}</div>
    @endif
@endif
@endsection
