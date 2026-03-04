@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
<div class="max-w-md mx-auto">
    <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-8 md:p-10">
        <h1 class="font-display text-3xl font-bold text-[#e50914] tracking-widest mb-2">INICIAR SESIÓN</h1>
        <p class="text-white/80 mb-8">Ingresa tu correo y contraseña para acceder.</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-white/80 mb-1">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" maxlength="255"
                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914] focus:border-[#e50914] @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-white/80 mb-1">Contraseña</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" maxlength="255"
                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914] focus:border-[#e50914] @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center">
                <input id="remember" type="checkbox" name="remember"
                       class="rounded border-red-900/50 text-[#e50914] focus:ring-[#e50914] bg-black/60">
                <label for="remember" class="ml-2 text-sm text-white/70">Recordarme</label>
            </div>

            @if(config('services.recaptcha.site_key'))
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            @endif
            @error('g-recaptcha-response')
                <p class="text-sm text-red-400">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full rounded-xl bg-[#e50914] px-5 py-3.5 text-white font-bold hover:bg-red-600 transition">
                Iniciar sesión
            </button>
        </form>

        <p class="mt-6 text-center text-white/70">
            ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-medium text-[#e50914] hover:text-red-400">Regístrate</a>
        </p>
    </div>
</div>
@endsection
