@extends('layouts.app')

@section('title', 'Reservar - ' . $event->name)

@section('content')
<div class="max-w-lg mx-auto">
    <div class="rounded-2xl border border-purple-900/50 bg-black/60 backdrop-blur p-8 md:p-10">
        <h1 class="font-display text-3xl font-bold text-[#ff2daa] tracking-widest mb-2">RESERVAR</h1>
        <p class="text-white/80 mb-2">{{ $event->name }}</p>
        <p class="text-white/60 text-sm mb-8">Elige cómo quieres continuar.</p>

        <div class="space-y-3">
            <a href="{{ route('login') }}"
               class="flex w-full items-center justify-center rounded-xl bg-[#ff2daa] px-5 py-4 text-white font-bold hover:bg-pink-600 transition">
                Iniciar sesión
            </a>
            <a href="{{ route('register') }}"
               class="flex w-full items-center justify-center rounded-xl border border-white/30 bg-white/10 px-5 py-4 text-white font-semibold hover:bg-white/20 transition">
                Registrarse
            </a>
            <a href="{{ route('reservations.create', $event) }}"
               class="flex w-full items-center justify-center rounded-xl border border-purple-900/50 bg-black/40 px-5 py-4 text-white/90 font-medium hover:border-[#39ff14]/50 hover:text-[#39ff14] transition">
                Continuar como invitado
            </a>
        </div>
    </div>
</div>
@endsection
