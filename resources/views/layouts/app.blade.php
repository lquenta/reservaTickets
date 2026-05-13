<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'NOVA') }} - @yield('title', 'Inicio')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bebas-neue:400|outfit:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>document.addEventListener('alpine:init', () => { Alpine.store('scrollSpy', { activeSection: '' }); });</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @if(config('services.recaptcha.site_key'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
    @stack('styles')
</head>
<body class="min-h-screen bg-black text-white font-sans antialiased overflow-x-hidden homepage-effects homepage-scanlines-global">
    {{-- Stranger Things: vignette, static, ash, gate glow --}}
    <div class="homepage-vignette" aria-hidden="true"></div>
    <div class="homepage-gate-glow" aria-hidden="true"></div>
    <div class="homepage-static" aria-hidden="true"></div>
    <div class="homepage-ash" aria-hidden="true">
        @for ($i = 0; $i < 55; $i++)
            @php
                $left = ($i * 1.82 + 7) % 100;
                $delay = ($i * 0.31) % 18;
                $duration = 14 + ($i % 10);
                $size = $i % 3;
                $driftLeft = $i % 2 === 0;
            @endphp
            <span class="homepage-ash-particle @if($size === 1) homepage-ash-particle--medium @elseif($size === 2) homepage-ash-particle--small @endif @if($driftLeft) homepage-ash-particle--left @endif"
                  style="left: {{ $left }}%; animation-delay: -{{ $delay }}s; animation-duration: {{ $duration }}s;"></span>
        @endfor
    </div>

    <div id="app-toast" class="fixed top-4 right-4 z-50 space-y-2" x-data="{ toasts: [] }" x-on:toast.window="toasts.push($event.detail); setTimeout(() => toasts.shift(), 4000)">
        <template x-for="(t, i) in toasts" :key="i">
            <div class="px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium"
                 :class="t.type === 'error' ? 'bg-red-600' : (t.type === 'success' ? 'bg-emerald-600' : 'bg-amber-600')"
                 x-text="t.message" x-show="true" x-transition></div>
        </template>
    </div>

    <div x-data="{ scrolled: false, mobileNavOpen: false }"
         x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 60; }); $watch('mobileNavOpen', open => document.body.classList.toggle('overflow-hidden', open)); @if(request()->routeIs('home')) $nextTick(() => { const ids = ['hero', 'quienes-somos', 'nuestros-eventos', 'contacto', 'boletin']; const observer = new IntersectionObserver((entries) => { const visible = entries.filter(e => e.isIntersecting).sort((a,b) => a.boundingClientRect.top - b.boundingClientRect.top); if (visible.length) Alpine.store('scrollSpy').activeSection = visible[0].target.id; }, { rootMargin: '-15% 0px -55% 0px', threshold: 0 }); ids.forEach(id => { const el = document.getElementById(id); if (el) observer.observe(el); }); }); @endif"
         @keydown.escape.window="mobileNavOpen = false">
    <header class="fixed top-0 left-0 right-0 transition-all duration-300" :class="mobileNavOpen ? 'z-[100]' : 'z-40'">
        <nav class="px-4 sm:px-6 lg:px-8 py-4" :class="scrolled || mobileNavOpen ? 'bg-black/95 backdrop-blur border-b border-red-900/50' : 'bg-transparent'">
            <div class="max-w-7xl mx-auto relative z-[110] flex justify-between items-center gap-3">
                <a href="{{ route('home') }}" class="text-xl font-bold tracking-widest text-[#e50914] hover:text-red-400 transition font-display shrink-0">
                    NOVA
                </a>
                <div class="hidden lg:flex items-center gap-4 xl:gap-6 flex-wrap justify-end" x-data x-effect="$store.scrollSpy.activeSection">
                    <a href="{{ route('home') }}#quienes-somos" class="text-sm transition tracking-wide" :class="$store.scrollSpy.activeSection === 'quienes-somos' ? 'text-[#e50914] font-semibold' : 'text-white/80 hover:text-[#e50914]'">Quiénes somos</a>
                    <a href="{{ route('home') }}#nuestros-eventos" class="text-sm transition tracking-wide" :class="$store.scrollSpy.activeSection === 'nuestros-eventos' ? 'text-[#e50914] font-semibold' : 'text-white/80 hover:text-[#e50914]'">Eventos</a>
                    <a href="{{ route('home') }}#contacto" class="text-sm transition tracking-wide" :class="$store.scrollSpy.activeSection === 'contacto' ? 'text-[#e50914] font-semibold' : 'text-white/80 hover:text-[#e50914]'">Contacto</a>
                    <a href="{{ route('home') }}#boletin" class="text-sm transition tracking-wide" :class="$store.scrollSpy.activeSection === 'boletin' ? 'text-[#e50914] font-semibold' : 'text-white/80 hover:text-[#e50914]'">Boletín</a>
                    <a href="{{ route('events.index') }}" class="text-sm font-semibold text-[#e50914] border border-[#e50914] px-4 py-2 rounded hover:bg-[#e50914] hover:text-black transition">Eventos</a>
                    @auth
                        @if(!auth()->user()->isAdmin())
                            <a href="{{ route('reservations.index') }}" class="text-sm text-white/80 hover:text-[#e50914] transition">Mis reservas</a>
                        @endif
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="text-sm text-[#e50914] font-semibold">Admin</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-white/60 hover:text-red-400 transition">Cerrar sesión</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-white/80 hover:text-[#e50914] transition">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="text-sm font-semibold bg-[#e50914] text-white px-4 py-2 rounded hover:bg-red-600 transition">Registrarse</a>
                    @endauth
                </div>
                <button type="button"
                        class="lg:hidden shrink-0 p-2 rounded-lg text-white hover:bg-white/10 border border-white/25 transition"
                        @click="mobileNavOpen = !mobileNavOpen"
                        :aria-expanded="mobileNavOpen"
                        aria-controls="mobile-primary-nav"
                        aria-label="Abrir o cerrar menú">
                    <svg x-show="!mobileNavOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileNavOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="mobile-primary-nav"
                 x-show="mobileNavOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="lg:hidden fixed inset-0 z-[90]"
                 role="dialog"
                 aria-modal="true"
                 aria-label="Menú de navegación">
                <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="mobileNavOpen = false"></div>
                <div class="absolute inset-y-0 right-0 z-10 w-full max-w-sm border-l border-red-900/50 bg-black/98 shadow-2xl flex flex-col overflow-y-auto overscroll-contain p-6 pt-20 gap-1"
                     @click.stop>
                    <div class="flex flex-col gap-1 pb-4 border-b border-red-900/40 mb-2" x-data x-effect="$store.scrollSpy.activeSection">
                        <a href="{{ route('home') }}#quienes-somos" @click="mobileNavOpen = false" class="py-3 px-3 rounded-lg text-base transition" :class="$store.scrollSpy.activeSection === 'quienes-somos' ? 'text-[#e50914] font-semibold bg-red-950/40' : 'text-white/90 hover:bg-white/5'">Quiénes somos</a>
                        <a href="{{ route('home') }}#nuestros-eventos" @click="mobileNavOpen = false" class="py-3 px-3 rounded-lg text-base transition" :class="$store.scrollSpy.activeSection === 'nuestros-eventos' ? 'text-[#e50914] font-semibold bg-red-950/40' : 'text-white/90 hover:bg-white/5'">Eventos</a>
                        <a href="{{ route('home') }}#contacto" @click="mobileNavOpen = false" class="py-3 px-3 rounded-lg text-base transition" :class="$store.scrollSpy.activeSection === 'contacto' ? 'text-[#e50914] font-semibold bg-red-950/40' : 'text-white/90 hover:bg-white/5'">Contacto</a>
                        <a href="{{ route('home') }}#boletin" @click="mobileNavOpen = false" class="py-3 px-3 rounded-lg text-base transition" :class="$store.scrollSpy.activeSection === 'boletin' ? 'text-[#e50914] font-semibold bg-red-950/40' : 'text-white/90 hover:bg-white/5'">Boletín</a>
                    </div>
                    <a href="{{ route('events.index') }}" @click="mobileNavOpen = false" class="py-3 px-3 rounded-lg text-base font-semibold text-[#e50914] border border-[#e50914] hover:bg-[#e50914] hover:text-black transition text-center">Ver eventos</a>
                    <div class="mt-4 pt-4 border-t border-red-900/40 flex flex-col gap-2">
                        @auth
                            @if(!auth()->user()->isAdmin())
                                <a href="{{ route('reservations.index') }}" @click="mobileNavOpen = false" class="py-3 px-3 rounded-lg text-base text-white/90 hover:bg-white/5 transition">Mis reservas</a>
                            @endif
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" @click="mobileNavOpen = false" class="py-3 px-3 rounded-lg text-base text-[#e50914] font-semibold hover:bg-white/5 transition">Admin</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                @csrf
                                <button type="submit" class="w-full py-3 px-3 rounded-lg text-base text-left text-white/70 hover:bg-white/5 hover:text-red-400 transition">Cerrar sesión</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" @click="mobileNavOpen = false" class="py-3 px-3 rounded-lg text-base text-white/90 hover:bg-white/5 transition">Iniciar sesión</a>
                            <a href="{{ route('register') }}" @click="mobileNavOpen = false" class="py-3 px-3 rounded-lg text-base font-semibold bg-[#e50914] text-white text-center rounded-lg hover:bg-red-600 transition">Registrarse</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="relative z-10 pt-24 @yield('mainClass', 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12')">
        @if(session('message'))
            <div class="fixed top-20 right-4 z-50" x-data="{ open: true }" x-show="open" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-4" role="alert">
                <div class="flex items-center gap-3 px-5 py-4 rounded-lg shadow-2xl bg-[#e50914] text-white font-medium text-sm border border-red-400/50">
                    <span class="flex-1">{{ session('message') }}</span>
                    <button type="button" @click="open = false" class="shrink-0 p-1 rounded hover:bg-white/20 transition" aria-label="Cerrar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        @endif

        @auth
            @if(!auth()->user()->isAdmin() && ($hasReservationInProgress ?? false) && !request()->routeIs('reservations.index') && !request()->routeIs('checkout.*'))
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                     x-data="{ open: true }"
                     x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="reservation-modal-title">
                    <div class="relative w-full max-w-md rounded-2xl border border-red-900/50 bg-black/95 shadow-2xl p-8 text-center"
                         x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         @click.stop>
                        <h2 id="reservation-modal-title" class="font-display text-2xl tracking-widest text-[#e50914] mb-3">Reserva en proceso</h2>
                        <p class="text-white/80 text-sm mb-6">Tienes una reserva sin completar. Completa el pago antes de que expire para no perder tus entradas.</p>
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <a href="{{ route('reservations.index') }}"
                               class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-[#e50914] text-white font-semibold hover:bg-red-600 transition">
                                Ir a Mis reservas
                            </a>
                            <button type="button"
                                    @click="open = false"
                                    class="inline-flex items-center justify-center px-6 py-3 rounded-xl border border-white/30 text-white/80 hover:bg-white/10 transition">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

        @yield('content')
    </main>

    <footer class="relative z-10 mt-16 border-t border-red-900/50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <span class="text-white/50 text-sm">NOVA — Reserva de tickets</span>
            <div class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm">
                <a href="{{ route('events.index') }}" class="text-white/50 hover:text-[#e50914] transition">Eventos</a>
                <a href="{{ route('terms') }}" class="text-white/50 hover:text-[#e50914] transition">Términos y condiciones</a>
                <a href="{{ route('home') }}#contacto" class="text-white/50 hover:text-[#e50914] transition">Contacto</a>
                @guest
                    <a href="{{ route('login') }}" class="text-white/50 hover:text-[#e50914] transition">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="text-white/50 hover:text-[#e50914] transition">Registrarse</a>
                @endguest
            </div>
        </div>
    </footer>

    </div>
    @stack('scripts')
</body>
</html>
