@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
<section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-violet-600 via-fuchsia-600 to-violet-700 p-10 md:p-16 lg:p-20 text-white shadow-2xl mb-16">
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.08\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-80"></div>
    <div class="relative max-w-3xl">
        <h1 class="text-4xl md:text-6xl font-extrabold mb-4 drop-shadow-lg">
            NOVA
        </h1>
        <p class="text-lg md:text-xl text-white/90 mb-8 leading-relaxed">
            Encuentra eventos increíbles y reserva de 1 a 4 tickets con NOVA. Tu próxima experiencia te espera.
        </p>
        <a href="{{ route('events.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-white text-violet-700 font-bold px-8 py-4 text-lg shadow-xl hover:bg-violet-50 hover:scale-[1.02] transition">
            Ver eventos disponibles
            <span aria-hidden="true">→</span>
        </a>
    </div>
</section>

<section class="grid md:grid-cols-3 gap-8 mb-16">
    <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-8 shadow-lg hover:shadow-violet-500/10 transition">
        <div class="text-4xl mb-4">🎫</div>
        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Elige tu evento</h2>
        <p class="text-slate-600 dark:text-slate-400">Explora los eventos disponibles y selecciona el que más te guste.</p>
    </div>
    <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-8 shadow-lg hover:shadow-violet-500/10 transition">
        <div class="text-4xl mb-4">⏱️</div>
        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Reserva en minutos</h2>
        <p class="text-slate-600 dark:text-slate-400">Reserva de 1 a 4 boletos con un código único de pago. Tienes 10 minutos para completar.</p>
    </div>
    <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-8 shadow-lg hover:shadow-violet-500/10 transition">
        <div class="text-4xl mb-4">✉️</div>
        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Boletos por correo</h2>
        <p class="text-slate-600 dark:text-slate-400">Tras confirmar el pago, recibirás tus tickets en tu correo electrónico.</p>
    </div>
</section>
@endsection
