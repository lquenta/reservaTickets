@extends('layouts.app')

@section('content')
<div class="flex flex-col lg:flex-row gap-8 lg:gap-10"
     x-data="{ adminMenuOpen: false }"
     @keydown.escape.window="adminMenuOpen = false">
    <aside class="lg:w-56 shrink-0">
        <div class="sticky top-24 z-20 max-lg:max-h-[min(70vh,calc(100dvh-7rem))] max-lg:overflow-y-auto lg:max-h-none"
             @click.outside="adminMenuOpen = false">
            <button type="button"
                    class="lg:hidden w-full flex items-center justify-between gap-3 rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-3 text-left text-sm font-semibold text-white/90 hover:bg-red-900/20 transition mb-3"
                    @click="adminMenuOpen = !adminMenuOpen"
                    :aria-expanded="adminMenuOpen"
                    aria-controls="admin-sidebar-nav">
                <span class="truncate">Menú de administración</span>
                <svg class="w-5 h-5 shrink-0 text-[#e50914] transition-transform duration-200" :class="adminMenuOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <nav id="admin-sidebar-nav"
                 class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-4 hidden lg:block"
                 :class="{ '!block': adminMenuOpen }">
                <p class="hidden lg:block text-xs font-semibold uppercase tracking-wider text-white/50 px-2 pb-3 mb-3 border-b border-red-900/50">Administración</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">📊</span> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.venues.index') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.venues.*') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">🏟️</span> Lugares
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.events.index') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.events.*') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">🎫</span> Eventos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.reservations.index') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.reservations.*') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">📋</span> Reservas
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.users.index') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">👥</span> Usuarios
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.reports.index') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.reports.*') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">📈</span> Reportes
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.hero-slides.index') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.hero-slides.*') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">🖼️</span> Slider inicio
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.site-content.quienes-somos') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.site-content.quienes-somos') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">📝</span> Quiénes somos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.team-members.index') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.team-members.*') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">👤</span> Integrantes
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.site-content.hero') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.site-content.hero') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">🏠</span> Texto Hero
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.mail-settings.index') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.mail-settings.*') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">✉️</span> Correo / SMTP
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.notification-settings.index') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/80 hover:bg-red-900/30 hover:text-[#e50914] font-medium transition {{ request()->routeIs('admin.notification-settings.*') ? 'bg-red-900/30 text-[#e50914]' : '' }}">
                            <span aria-hidden="true">🔔</span> Notificaciones
                        </a>
                    </li>
                </ul>
                <div class="mt-4 pt-3 border-t border-red-900/50">
                    <a href="{{ route('home') }}" @click="adminMenuOpen = false" class="flex items-center gap-2 px-3 py-2 rounded-xl text-white/50 hover:bg-red-900/20 hover:text-[#e50914] text-sm transition">← Volver al sitio</a>
                </div>
            </nav>
        </div>
    </aside>
    <main class="flex-1 min-w-0">
        @if(session('message'))
            <div class="mb-6 rounded-2xl border border-emerald-700 bg-emerald-900/30 px-4 py-3 text-emerald-200 font-medium" x-data="{ open: true }" x-show="open" x-transition>
                {{ session('message') }}
                <button type="button" @click="open = false" class="float-right p-1 rounded hover:bg-emerald-800/50" aria-label="Cerrar">×</button>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 rounded-2xl border border-red-700 bg-red-900/30 px-4 py-3 text-red-200 font-medium" x-data="{ open: true }" x-show="open" x-transition>
                {{ session('error') }}
                <button type="button" @click="open = false" class="float-right p-1 rounded hover:bg-red-800/50" aria-label="Cerrar">×</button>
            </div>
        @endif

        @php $adminAlerts = $adminAlerts ?? ['pending_reservations_count' => 0, 'events_low_stock' => collect()]; @endphp
        @if($adminAlerts['pending_reservations_count'] > 0)
            <div class="mb-6 rounded-2xl border-2 border-amber-500 bg-amber-900/40 px-4 py-3 text-amber-100 font-medium flex flex-wrap items-center gap-2">
                <span aria-hidden="true">⚠️</span>
                <span>
                    <strong>{{ $adminAlerts['pending_reservations_count'] }}</strong> reserva(s) pendiente(s) de revisión.
                </span>
                <a href="{{ route('admin.reservations.index', ['status' => 'PENDIENTE_PAGO']) }}" class="ml-2 rounded-lg bg-amber-600 hover:bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white transition">Revisar reservas</a>
            </div>
        @endif
        @if($adminAlerts['events_low_stock']->isNotEmpty())
            <div class="mb-6 rounded-2xl border-2 border-orange-500 bg-orange-900/40 px-4 py-3 text-orange-100 font-medium">
                <p class="flex items-center gap-2 mb-2">
                    <span aria-hidden="true">📉</span>
                    <strong>Entradas agotándose</strong> en {{ $adminAlerts['events_low_stock']->count() }} evento(s):
                </p>
                <ul class="list-disc list-inside text-sm space-y-0.5">
                    @foreach($adminAlerts['events_low_stock'] as $ev)
                        <li><a href="{{ route('admin.events.index') }}" class="underline hover:text-orange-200">{{ $ev->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('admin')
    </main>
</div>
@endsection
