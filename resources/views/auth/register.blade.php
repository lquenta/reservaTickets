@extends('layouts.app')

@section('title', 'Registrarse')

@section('content')
<div class="max-w-md mx-auto">
    <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-8 md:p-10">
        <h1 class="font-display text-3xl font-bold text-[#e50914] tracking-widest mb-2">CREAR CUENTA</h1>
        <p class="text-white/80 mb-8">Completa tus datos para reservar tickets.</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-white/80 mb-1">Nombre completo</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" maxlength="255"
                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914] focus:border-[#e50914] @error('name') border-red-500 @enderror"
                       pattern="[\p{L}\s\-\.']+">
                @error('name')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-white/80 mb-1">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" maxlength="255"
                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914] focus:border-[#e50914] @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="ci" class="block text-sm font-medium text-white/80 mb-1">CI</label>
                <input id="ci" type="text" name="ci" value="{{ old('ci') }}" required maxlength="15"
                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914] @error('ci') border-red-500 @enderror"
                       pattern="[0-9\-]+">
                @error('ci')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-white/80 mb-1">Teléfono celular</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required maxlength="20"
                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914] @error('phone') border-red-500 @enderror"
                       pattern="[0-9\s+\-]+">
                @error('phone')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-white/80 mb-1">Contraseña</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" minlength="8" maxlength="255"
                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914] @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-white/80 mb-1">Confirmar contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" maxlength="255"
                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914]">
            </div>

            @if(config('services.recaptcha.site_key'))
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            @endif
            @error('g-recaptcha-response')
                <p class="text-sm text-red-400">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full rounded-xl bg-[#e50914] px-5 py-3.5 text-white font-bold hover:bg-red-600 transition">
                Registrarse
            </button>
        </form>

        <p class="mt-6 text-center text-white/70">
            ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-medium text-[#e50914] hover:text-red-400">Iniciar sesión</a>
        </p>
    </div>
</div>
@endsection
