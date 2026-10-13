@extends('layouts.app')

@section('title', 'Nueva contraseña')

@section('content')
<div class="max-w-md mx-auto">
    <div class="rounded-2xl border border-purple-900/50 bg-black/60 backdrop-blur p-8 md:p-10">
        <h1 class="font-display text-3xl font-bold text-[#ff2daa] tracking-widest mb-2">NUEVA CLAVE</h1>
        <p class="text-white/80 mb-8">Elige una contraseña de al menos 8 caracteres.</p>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-sm font-medium text-white/80 mb-1">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" maxlength="255"
                       class="w-full rounded-xl border border-purple-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#39ff14] focus:border-[#39ff14] @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-white/80 mb-1">Contraseña nueva</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" maxlength="255"
                       class="w-full rounded-xl border border-purple-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#39ff14] @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-white/80 mb-1">Confirmar contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" maxlength="255"
                       class="w-full rounded-xl border border-purple-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#39ff14]">
            </div>

            <button type="submit" class="w-full rounded-xl bg-[#ff2daa] px-5 py-3.5 text-white font-bold hover:bg-pink-600 transition">
                Guardar contraseña
            </button>
        </form>
    </div>
</div>
@endsection
