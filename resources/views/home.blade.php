@extends('layouts.app')

@section('title', 'Inicio')

@section('mainClass', 'max-w-none px-0 py-0')

@section('content')
{{-- Global section reveal state --}}
<div x-data="homeReveal()" x-init="init()">
    {{-- Hero: pull up so it’s full viewport under fixed nav --}}
    <section class="relative min-h-screen flex items-center justify-center homepage-scanlines -mt-24 pt-24 overflow-hidden" id="hero"
             x-data="heroSlider({{ count($hero_slides ?? []) }}, {{ json_encode($hero_slides ?? []) }})"
             x-init="start()">
        @if(!empty($hero_video_url))
            <div class="absolute inset-0 z-0">
                <video class="absolute inset-0 w-full h-full object-cover" autoplay muted loop playsinline aria-hidden="true"
                       src="{{ $hero_video_url }}"></video>
            </div>
            <div class="absolute inset-0 bg-black/50 z-[1]" aria-hidden="true"></div>
        @elseif(!empty($hero_slides))
            <template x-for="(url, i) in slides" :key="i">
                <div class="absolute inset-0 bg-cover bg-center transition-opacity duration-1000"
                     :style="'background-image: url(\'' + url + '\')'"
                     :class="{ 'opacity-100 z-0': activeIndex === i, 'opacity-0 z-0': activeIndex !== i }"
                     aria-hidden="true"></div>
            </template>
            <div class="absolute inset-0 bg-black/50 z-[1]" aria-hidden="true"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-b from-black via-[#1a0505] to-black z-0"></div>
        @endif
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_0%,rgba(229,9,20,0.15),transparent)] z-[1]" aria-hidden="true"></div>
        <div class="relative z-10 text-center px-4 max-w-4xl mx-auto" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) { visible = true; o.disconnect() } }, { threshold: 0.1 }); o.observe($el)">
            <h1 class="font-display text-5xl sm:text-7xl md:text-8xl lg:text-9xl tracking-widest text-[#e50914] animate-flicker text-glow st-glow-title mb-6"
                x-show="visible"
                x-transition:enter="transition ease-out duration-1000"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">
                NOVA
            </h1>
            <p class="text-xl sm:text-2xl text-white/80 tracking-widest uppercase mb-4"
                x-show="visible"
                x-transition:enter="transition ease-out duration-700 delay-200"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0">
                {{ $hero_content?->title ?? 'Tus entradas. Tu experiencia.' }}
            </p>
            <a href="{{ route('events.index') }}"
                class="inline-block mt-8 px-10 py-4 bg-[#e50914] text-white font-bold tracking-widest text-lg rounded border-2 border-[#e50914] hover:bg-transparent hover:text-[#e50914] transition-all duration-300 st-glow-btn"
                x-show="visible"
                x-transition:enter="transition ease-out duration-700 delay-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0">
                {{ $hero_content?->content ?? 'ENTRAR' }}
            </a>
        </div>
    </section>

    {{-- Quiénes somos: contenido editable por admin --}}
    <section id="quienes-somos" class="relative min-h-screen flex items-center justify-center py-20 px-4 section-stranger-bg" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.1 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 max-w-3xl mx-auto text-center"
            x-show="visible"
            x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-8"
            x-transition:enter-end="opacity-100 translate-y-0">
            <h2 class="font-display text-4xl sm:text-5xl md:text-6xl tracking-widest text-[#e50914] mb-8 st-glow-title">{{ $quienes_somos?->title ?? 'QUIÉNES SOMOS' }}</h2>
            <div class="text-white/90 text-lg leading-relaxed space-y-4 text-left">
                @if($quienes_somos && $quienes_somos->content)
                    @foreach(explode("\n\n", $quienes_somos->content) as $paragraph)
                        @if(trim($paragraph))
                            <p class="text-white/90">{{ trim($paragraph) }}</p>
                        @endif
                    @endforeach
                @else
                    <p class="text-white/90">NOVA es tu plataforma para descubrir eventos y reservar tickets de forma rápida y segura. Conectamos organizadores con el público: elige tu evento, reserva de 1 a 4 entradas con un código único de pago y recibe tus tickets por correo.</p>
                    <p class="text-white/70 text-base">Simple, transparente y pensado para que no te pierdas nada.</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Nuestros eventos --}}
    <section id="nuestros-eventos" class="relative min-h-screen flex flex-col items-center justify-center py-20 px-4 section-stranger-bg" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.1 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 w-full max-w-6xl mx-auto" x-show="visible" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
            <h2 class="font-display text-4xl sm:text-5xl md:text-6xl tracking-widest text-[#e50914] text-center mb-12 st-glow-title">
                NUESTROS EVENTOS
            </h2>

            @if($featured_events->isNotEmpty())
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3 mb-12">
                    @foreach($featured_events as $event)
                        <article class="group relative overflow-hidden rounded-lg border border-red-900/50 bg-black/80 backdrop-blur hover:border-[#e50914]/60 transition-all duration-300">
                            <div class="aspect-[4/3] bg-cover bg-center @if(!$event->cover_image_path) bg-gradient-to-br from-[#1a0505] to-[#e50914]/20 @endif"
                                @if($event->cover_image_path) style="background-image: url('{{ asset('storage/'.$event->cover_image_path) }}');" @endif>
                                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
                            </div>
                            <div class="p-5">
                                <h3 class="text-xl font-bold text-white mb-2">{{ $event->name }}</h3>
                                <p class="text-white/70 text-sm mb-2">{{ $event->starts_at->translatedFormat('l d F Y, H:i') }}</p>
                                <p class="text-white/60 text-sm mb-4">{{ $event->venue }}</p>
                                @auth
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('admin.dashboard') }}" class="inline-block text-sm text-white/70 hover:text-[#e50914] transition">Panel admin</a>
                                    @else
                                        <a href="{{ route('reservations.create', $event) }}" class="inline-block text-sm font-semibold text-[#e50914] hover:text-red-400 transition">Reservar →</a>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="inline-block text-sm text-white/70 hover:text-[#e50914] transition">Inicia sesión para reservar</a>
                                @endauth
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <div class="text-center">
                <a href="{{ route('events.index') }}" class="inline-block px-8 py-3 bg-[#e50914] text-white font-bold tracking-widest rounded hover:bg-red-600 transition st-glow-btn">
                    VER TODOS LOS EVENTOS
                </a>
            </div>
        </div>
    </section>

    {{-- Contáctenos --}}
    <section id="contacto" class="relative min-h-screen flex items-center justify-center py-20 px-4 section-stranger-bg" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.1 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 w-full max-w-2xl mx-auto" x-show="visible" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
            <h2 class="font-display text-4xl sm:text-5xl tracking-widest text-[#e50914] text-center mb-10 st-glow-title">
                CONTÁCTENOS
            </h2>

            <div class="grid gap-8 md:grid-cols-2 mb-10">
                <div class="text-center md:text-left">
                    <p class="text-white/80 mb-2">Email</p>
                    <a href="mailto:{{ config('mail.from.address', 'contacto@nova.com') }}" class="text-[#e50914] hover:text-red-400 transition">{{ config('mail.from.address', 'contacto@nova.com') }}</a>
                </div>
                <div class="text-center md:text-left">
                    <p class="text-white/80 mb-2">¿Dudas?</p>
                    <p class="text-white/90">Escríbenos y te respondemos a la brevedad.</p>
                </div>
            </div>

            <form action="{{ route('contact.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="contact-name" class="block text-sm text-white/80 mb-1">Nombre</label>
                    <input type="text" name="name" id="contact-name" value="{{ old('name') }}" required
                        class="w-full px-4 py-3 bg-black/60 border border-red-900/50 rounded text-white placeholder-white/40 focus:border-[#e50914] focus:ring-1 focus:ring-[#e50914] outline-none transition">
                    @error('name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="contact-email" class="block text-sm text-white/80 mb-1">Correo</label>
                    <input type="email" name="email" id="contact-email" value="{{ old('email') }}" required
                        class="w-full px-4 py-3 bg-black/60 border border-red-900/50 rounded text-white placeholder-white/40 focus:border-[#e50914] focus:ring-1 focus:ring-[#e50914] outline-none transition">
                    @error('email')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="contact-message" class="block text-sm text-white/80 mb-1">Mensaje</label>
                    <textarea name="message" id="contact-message" rows="4" required
                        class="w-full px-4 py-3 bg-black/60 border border-red-900/50 rounded text-white placeholder-white/40 focus:border-[#e50914] focus:ring-1 focus:ring-[#e50914] outline-none transition resize-none">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full py-3 bg-[#e50914] text-white font-bold tracking-widest rounded hover:bg-red-600 transition st-glow-btn">
                    ENVIAR
                </button>
            </form>
        </div>
    </section>

    {{-- Boletín --}}
    <section id="boletin" class="relative min-h-screen flex items-center justify-center py-20 px-4 section-stranger-bg" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.1 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 w-full max-w-xl mx-auto text-center" x-show="visible" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
            <h2 class="font-display text-4xl sm:text-5xl tracking-widest text-[#e50914] mb-4 st-glow-title">
                BOLETÍN
            </h2>
            <p class="text-white/80 mb-8">
                Suscríbete y recibe novedades y próximos eventos.
            </p>

            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                @csrf
                <input type="email" name="email" placeholder="tu@correo.com" required
                    class="flex-1 px-4 py-3 bg-black/60 border border-red-900/50 rounded text-white placeholder-white/40 focus:border-[#e50914] focus:ring-1 focus:ring-[#e50914] outline-none transition">
                <button type="submit" class="px-8 py-3 bg-[#e50914] text-white font-bold tracking-widest rounded hover:bg-red-600 transition whitespace-nowrap st-glow-btn">
                    SUSCRIBIRME
                </button>
            </form>
            @error('email')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </section>

    {{-- Cierre CTA --}}
    <section class="relative py-24 px-4 flex flex-col items-center justify-center min-h-[50vh] section-stranger-bg" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.1 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 text-center" x-show="visible" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
            <p class="text-white/80 text-lg mb-6">No te quedes fuera.</p>
            <a href="{{ route('events.index') }}" class="inline-block px-10 py-4 bg-[#e50914] text-white font-bold tracking-widest rounded border-2 border-[#e50914] hover:bg-transparent hover:text-[#e50914] transition st-glow-btn">
                RESERVAR ENTRADAS
            </a>
        </div>
    </section>
</div>

<script>
function homeReveal() {
    return { init() {} };
}
function heroSlider(count, slides) {
    return {
        slides: Array.isArray(slides) ? slides : [],
        activeIndex: 0,
        interval: null,
        start() {
            if (this.slides.length <= 1) return;
            this.interval = setInterval(() => {
                this.activeIndex = (this.activeIndex + 1) % this.slides.length;
            }, 5500);
        }
    };
}
</script>
@endsection
