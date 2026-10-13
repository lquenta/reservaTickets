@extends('layouts.app')

@section('title', 'Recuperar contraseña')

@section('content')
<div class="max-w-md mx-auto">
    <div class="rounded-2xl border border-purple-900/50 bg-black/60 backdrop-blur p-8 md:p-10">
        <h1 class="font-display text-3xl font-bold text-[#ff2daa] tracking-widest mb-2">RECUPERAR CLAVE</h1>
        <p class="text-white/80 mb-8">Escribe el correo de tu cuenta. Si está registrado, te enviamos un enlace para elegir una contraseña nueva.</p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-white/80 mb-1">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" maxlength="255"
                       class="w-full rounded-xl border border-purple-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#39ff14] focus:border-[#39ff14] @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full rounded-xl bg-[#ff2daa] px-5 py-3.5 text-white font-bold hover:bg-pink-600 transition">
                Enviar enlace
            </button>
        </form>

        <p class="mt-6 text-center text-white/70">
            <a href="{{ route('login') }}" class="font-medium text-[#ff2daa] hover:text-[#39ff14]">Volver a iniciar sesión</a>
        </p>
    </div>
</div>
@endsection
