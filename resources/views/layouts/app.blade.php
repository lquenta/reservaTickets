<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'NOVA') }} - @yield('title', 'Inicio')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>document.addEventListener('alpine:init', () => { Alpine.store('scrollSpy', { activeSection: '' }); });</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @if(config('services.recaptcha.site_key'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
    @stack('styles')
</head>
<body class="min-h-screen bg-[#0d061b] text-white font-sans antialiased overflow-x-hidden homepage-effects homepage-scanlines-global">
    {{-- Nebula: vignette, static, ash, gate glow --}}
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

    <div id="app-toast" class="fixed top-20 right-4 z-[100] w-[min(100vw-2rem,24rem)] space-y-2 pointer-events-none"
         x-data="{
            toasts: [],
            push(detail) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, message: detail.message || '', type: detail.type || 'success' });
                setTimeout(() => this.dismiss(id), detail.duration || 4500);
            },
            dismiss(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
         }"
         x-on:toast.window="push($event.detail)">
        <template x-for="t in toasts" :key="t.id">
            <div class="pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg text-sm font-medium"
                 role="alert"
                 x-show="true"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-4"
                 :class="{
                    'bg-emerald-50 border-emerald-300 text-emerald-900 dark:bg-emerald-900/90 dark:border-emerald-600 dark:text-emerald-50': t.type === 'success',
                    'bg-red-50 border-red-300 text-red-900 dark:bg-red-900/90 dark:border-red-600 dark:text-red-50': t.type === 'error',
                    'bg-amber-50 border-amber-300 text-amber-900 dark:bg-amber-900/90 dark:border-amber-600 dark:text-amber-50': t.type === 'warning' || t.type === 'warn',
                    'bg-sky-50 border-sky-300 text-sky-900 dark:bg-sky-900/90 dark:border-sky-600 dark:text-sky-50': t.type === 'info'
                 }">
                <span class="mt-0.5 shrink-0" aria-hidden="true"
                      x-text="t.type === 'error' ? '⚠' : (t.type === 'warning' || t.type === 'warn' ? '!' : (t.type === 'info' ? 'ℹ' : '✓'))"></span>
                <span class="flex-1 leading-snug" x-text="t.message"></span>
                <button type="button" class="shrink-0 rounded p-0.5 opacity-70 hover:opacity-100" @click="dismiss(t.id)" aria-label="Cerrar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    <div x-data="{ scrolled: false }"
         x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 60; }); @if(request()->routeIs('home')) $nextTick(() => { const ids = ['hero', 'videos', 'quienes-somos', 'nuestros-eventos', 'contacto', 'boletin']; const observer = new IntersectionObserver((entries) => { const visible = entries.filter(e => e.isIntersecting).sort((a,b) => a.boundingClientRect.top - b.boundingClientRect.top); if (visible.length) Alpine.store('scrollSpy').activeSection = visible[0].target.id; }, { rootMargin: '-15% 0px -55% 0px', threshold: 0 }); ids.forEach(id => { const el = document.getElementById(id); if (el) observer.observe(el); }); }); @endif">
    <header class="fixed top-0 left-0 right-0 z-40 transition-all duration-300">
        <nav class="px-3 sm:px-6 lg:px-8 py-3 sm:py-4" :class="scrolled ? 'bg-black/95 backdrop-blur border-b border-purple-900/50' : 'bg-transparent'">
            <div class="max-w-7xl mx-auto flex justify-between items-center gap-2">
                <a href="{{ route('home') }}" class="text-lg sm:text-xl font-bold tracking-widest text-[#ff2daa] hover:text-[#39ff14] transition font-display shrink-0">
                    NOVA
                </a>
                <div class="flex items-center gap-2 sm:gap-6 min-w-0" x-data x-effect="$store.scrollSpy.activeSection">
                    @if(request()->routeIs('home') && isset($featured_videos) && $featured_videos->isNotEmpty())
                    <a href="{{ route('home') }}#videos" class="text-sm transition tracking-wide hidden sm:inline" :class="$store.scrollSpy.activeSection === 'videos' ? 'text-[#ff2daa] font-semibold' : 'text-white/80 hover:text-[#39ff14]'">Videos</a>
                    @endif
                    <a href="{{ route('home') }}#quienes-somos" class="text-sm transition tracking-wide hidden sm:inline" :class="$store.scrollSpy.activeSection === 'quienes-somos' ? 'text-[#ff2daa] font-semibold' : 'text-white/80 hover:text-[#39ff14]'">Quiénes somos</a>
                    <a href="{{ route('home') }}#nuestros-eventos" class="text-sm transition tracking-wide hidden sm:inline" :class="$store.scrollSpy.activeSection === 'nuestros-eventos' ? 'text-[#ff2daa] font-semibold' : 'text-white/80 hover:text-[#39ff14]'">Eventos</a>
                    <a href="{{ route('home') }}#contacto" class="text-sm transition tracking-wide hidden sm:inline" :class="$store.scrollSpy.activeSection === 'contacto' ? 'text-[#ff2daa] font-semibold' : 'text-white/80 hover:text-[#39ff14]'">Contacto</a>
                    <a href="{{ route('home') }}#boletin" class="text-sm transition tracking-wide hidden sm:inline" :class="$store.scrollSpy.activeSection === 'boletin' ? 'text-[#ff2daa] font-semibold' : 'text-white/80 hover:text-[#39ff14]'">Boletín</a>
                    <a href="{{ route('events.index') }}" class="text-xs sm:text-sm font-semibold px-2.5 py-1.5 sm:px-4 sm:py-2 rounded transition btn-nova-secondary shrink-0">Eventos</a>
                    @auth
                        @if(auth()->user()->isVendedor())
                            <a href="{{ route('seller.events.index') }}" class="text-xs sm:text-sm text-[#ff2daa] font-semibold">Vender tickets</a>
                        @elseif(!auth()->user()->isAdmin())
                            <a href="{{ route('reservations.index') }}" class="text-xs sm:text-sm text-white/80 hover:text-[#39ff14] transition hidden sm:inline">Mis reservas</a>
                        @endif
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="text-xs sm:text-sm text-[#ff2daa] font-semibold">Admin</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs sm:text-sm text-white/60 hover:text-[#39ff14] transition">Cerrar sesión</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-white/80 hover:text-[#39ff14] transition hidden sm:inline">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="text-xs sm:text-sm font-semibold px-2.5 py-1.5 sm:px-4 sm:py-2 rounded transition btn-nova-primary shrink-0">Registrarse</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main class="relative z-10 pt-24 @yield('mainClass', 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12')">
        @if(session('message'))
            <div class="fixed top-20 right-4 z-50" x-data="{ open: true }" x-show="open" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-4" role="alert">
                <div class="flex items-center gap-3 px-5 py-4 rounded-lg shadow-2xl bg-[#ff2daa] text-white font-medium text-sm border border-pink-400/50">
                    <span class="flex-1">{{ session('message') }}</span>
                    <button type="button" @click="open = false" class="shrink-0 p-1 rounded hover:bg-white/20 transition" aria-label="Cerrar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        @endif

        @auth
            @if(!auth()->user()->isAdmin() && ($hasReservationInProgress ?? false) && !request()->routeIs('reservations.index') && !request()->routeIs('checkout.*') && !request()->routeIs('seller.*'))
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
                    <div class="relative w-full max-w-md rounded-2xl border border-purple-900/50 bg-black/95 shadow-2xl p-8 text-center"
                         x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         @click.stop>
                        <h2 id="reservation-modal-title" class="font-display text-2xl tracking-widest text-[#ff2daa] mb-3">Reserva en proceso</h2>
                        <p class="text-white/80 text-sm mb-6">Tienes una reserva sin completar. Completa el pago antes de que expire para no perder tus entradas.</p>
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <a href="{{ route('reservations.index') }}"
                               class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-[#ff2daa] text-white font-semibold hover:bg-pink-600 transition">
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

    <footer class="relative z-10 mt-16 border-t border-purple-900/50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <span class="text-white/50 text-sm">NOVA — Reserva de tickets</span>
            <div class="flex gap-6 text-sm">
                <a href="{{ route('events.index') }}" class="text-white/50 hover:text-[#39ff14] transition">Eventos</a>
                <a href="{{ route('terms') }}" class="text-white/50 hover:text-[#39ff14] transition">Términos y condiciones</a>
                <a href="{{ route('home') }}#contacto" class="text-white/50 hover:text-[#39ff14] transition">Contacto</a>
                @guest
                    <a href="{{ route('login') }}" class="text-white/50 hover:text-[#39ff14] transition">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="text-white/50 hover:text-[#39ff14] transition">Registrarse</a>
                @endguest
            </div>
        </div>
    </footer>

    </div>
    @stack('scripts')
</body>
</html>
