@extends('layouts.app')

@section('title', 'Verificar correo')

@section('content')
<div class="max-w-md mx-auto">
    <div class="rounded-2xl border border-fuchsia-900/50 bg-black/60 backdrop-blur p-8 md:p-10 text-center">
        <h1 class="font-display text-2xl font-bold text-[#e11d8a] tracking-widest mb-4">VERIFICA TU CORREO</h1>
        <p class="text-white/80 mb-6">Debes confirmar tu correo electrónico antes de reservar tickets. Revisa tu bandeja de entrada (y spam).</p>
        @if(session('message'))
            <p class="mb-4 text-emerald-400 text-sm">{{ session('message') }}</p>
        @endif
        <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
            @csrf
            <button type="submit" class="w-full rounded-xl bg-[#e11d8a] px-5 py-3 text-white font-bold hover:bg-fuchsia-700 transition">
                Reenviar enlace de verificación
            </button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-white/60 hover:text-white underline">Cerrar sesión</button>
        </form>
    </div>
</div>
@endsection
