@extends('layouts.app')

@section('title', 'Eventos')

@section('content')
<div class="mb-10">
    <h1 class="font-display text-4xl md:text-5xl font-bold text-[#e50914] tracking-widest mb-2">EVENTOS DISPONIBLES</h1>
    <p class="text-white/80 text-lg">Encuentra tu próximo evento y reserva tus tickets.</p>
</div>

@if($events->isEmpty())
    <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-16 text-center">
        <div class="text-6xl mb-4 opacity-50">🎫</div>
        <p class="text-white/80 text-lg">No hay eventos disponibles en este momento.</p>
        <p class="text-white/50 text-sm mt-2">Vuelve pronto para ver nuevas fechas.</p>
    </div>
@else
    <div x-data="{
        isOpen: false,
        selected: { id: null, name: '', description: '', date: '', venue: '', cover: '', reserve_url: '', login_url: '', admin_url: '' },
        open(el) {
            if (!el || !el.dataset) return;
            const d = el.dataset;
            if ((d.eventSoldOut || '') === '1') return;
            this.selected = {
                id: d.eventId ? parseInt(d.eventId, 10) : null,
                name: d.eventName || '',
                description: d.eventDescription || '',
                date: d.eventDate || '',
                venue: d.eventVenue || '',
                cover: d.eventCover || '',
                reserve_url: d.eventReserveUrl || '',
                login_url: d.eventLoginUrl || '',
                admin_url: d.eventAdminUrl || ''
            };
            if (typeof window.novaTrack === 'function' && this.selected.id) {
                window.novaTrack('view_event', { event_id: this.selected.id });
            }
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
        },
        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
        }
    }">
    <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">
        @foreach($events as $event)
            @php($isSoldOut = ! $event->is_active)
            <article class="group relative overflow-hidden rounded-2xl border border-red-900/50 hover:border-[#e50914]/50 transition-all duration-300 hover:-translate-y-1 min-h-[320px] flex flex-col cursor-pointer"
                     data-event-name="{{ e($event->name) }}"
                     data-event-id="{{ $event->id }}"
                     data-event-description="{{ e($event->description ?? '') }}"
                     data-event-date="{{ e($event->starts_at->translatedFormat('l d \d\e F \d\e Y, H:i')) }}"
                     data-event-venue="{{ e($event->venue ?? '') }}"
                     data-event-cover="{{ $event->cover_image_path ? asset('storage/'.$event->cover_image_path) : '' }}"
                     data-event-sold-out="{{ $isSoldOut ? '1' : '0' }}"
                     data-event-reserve-url="{{ auth()->check() && !auth()->user()->isAdmin() && !$isSoldOut ? route('reservations.create', $event) : '' }}"
                     data-event-login-url="{{ !auth()->check() ? route('login') : '' }}"
                     data-event-admin-url="{{ auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : '' }}"
                     @click="open($event.currentTarget)">
                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-500 group-hover:scale-105
                     @if(!$event->cover_image_path) bg-gradient-to-br from-[#1a0505] to-[#e50914]/20 @endif"
                     @if($event->cover_image_path) style="background-image: url('{{ asset('storage/'.$event->cover_image_path) }}');" @endif>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>

                <div class="relative flex flex-col flex-1 p-6 justify-end">
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-2xl font-bold text-white drop-shadow-lg">{{ $event->name }}</h2>
                        @if($isSoldOut)
                            <span class="inline-flex rounded-full bg-red-600/90 text-white px-2.5 py-1 text-xs font-bold tracking-wide">SOLD OUT</span>
                        @endif
                    </div>
                    <p class="text-white/90 text-sm mb-1 flex items-center gap-1">
                        <span aria-hidden="true">📅</span>
                        {{ $event->starts_at->translatedFormat('l d F Y, H:i') }}
                    </p>
                    <p class="text-white/80 text-sm mb-2 flex items-center gap-1">
                        <span aria-hidden="true">📍</span>
                        {{ $event->venue }}
                    </p>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" @click.stop class="inline-flex items-center justify-center rounded-xl bg-white/10 text-white font-medium px-5 py-3 border border-white/30 hover:bg-white/20 transition w-fit backdrop-blur mt-2">
                                Panel de administración
                            </a>
                        @else
                            @if($event->venue_id && !$isSoldOut)
                                <p class="text-white/60 text-xs mb-2">
                                    @if($event->hasSections())
                                        Ver secciones y butacas en el checkout.
                                    @else
                                        Reserva con elección de butaca en el checkout.
                                    @endif
                                </p>
                            @endif
                            @if($isSoldOut)
                                <span class="inline-flex items-center justify-center rounded-xl bg-white/10 text-white/70 font-semibold px-5 py-3 border border-white/20 w-fit mt-1 cursor-not-allowed">
                                    SOLD OUT
                                </span>
                            @else
                                <a href="{{ route('reservations.create', $event) }}" @click.stop class="inline-flex items-center justify-center rounded-xl bg-[#e50914] text-white font-semibold px-5 py-3 hover:bg-red-600 transition w-fit mt-1">
                                    Reservar tickets
                                </a>
                            @endif
                        @endif
                    @else
                        @if($isSoldOut)
                            <span class="inline-flex items-center justify-center rounded-xl bg-white/10 text-white/70 font-semibold px-5 py-3 border border-white/20 w-fit mt-2 cursor-not-allowed">
                                SOLD OUT
                            </span>
                        @else
                            <a href="{{ route('login') }}" @click.stop class="inline-flex items-center justify-center rounded-xl bg-white/10 text-white font-medium px-5 py-3 border border-white/30 hover:bg-white/20 transition w-fit backdrop-blur mt-2">
                                Inicia sesión para reservar
                            </a>
                        @endif
                    @endauth
                </div>
            </article>
        @endforeach
    </div>

    {{-- Modal evento: flyer atrás, detalle y botón reservar (sin teleport para mantener scope Alpine) --}}
    <div x-show="isOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="close()"
             role="dialog" aria-modal="true" aria-labelledby="event-modal-title">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="close()"></div>
            <div class="relative w-full max-w-2xl max-h-[90vh] overflow-hidden rounded-2xl border-2 border-red-900/50 bg-black/90 shadow-2xl flex flex-col"
                 @click.stop
                 x-show="isOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                {{-- Flyer de fondo --}}
                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-30"
                     :style="selected.cover ? { backgroundImage: 'url(' + selected.cover + ')' } : { background: 'linear-gradient(135deg, #1a0505 0%, rgba(229,9,20,0.2) 100%)' }"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/80 to-black/60"></div>

                <div class="relative flex flex-col flex-1 overflow-y-auto p-6 md:p-8">
                    <div class="flex justify-end mb-2">
                        <button type="button" @click="close()" class="rounded-full p-2 text-white/80 hover:text-white hover:bg-white/10 transition" aria-label="Cerrar">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <h2 id="event-modal-title" class="font-display text-3xl md:text-4xl font-bold text-[#e50914] tracking-wider mb-4" x-text="selected.name"></h2>
                    <p class="text-white/90 flex items-center gap-2 mb-2">
                        <span aria-hidden="true">📅</span>
                        <span x-text="selected.date"></span>
                    </p>
                    <p class="text-white/80 flex items-center gap-2 mb-4" x-show="selected.venue">
                        <span aria-hidden="true">📍</span>
                        <span x-text="selected.venue"></span>
                    </p>
                    <div class="prose prose-invert prose-sm max-w-none mb-6" x-show="selected.description">
                        <p class="text-white/80 whitespace-pre-wrap" x-text="selected.description"></p>
                    </div>
                    <div class="mt-auto pt-4 flex flex-wrap gap-3">
                        <template x-if="selected.reserve_url">
                            <a :href="selected.reserve_url" class="inline-flex items-center justify-center rounded-xl bg-[#e50914] text-white font-semibold px-6 py-3 hover:bg-red-600 transition">
                                Reservar tickets
                            </a>
                        </template>
                        <template x-if="selected.login_url">
                            <a :href="selected.login_url" class="inline-flex items-center justify-center rounded-xl bg-white/10 text-white font-medium px-6 py-3 border border-white/30 hover:bg-white/20 transition">
                                Inicia sesión para reservar
                            </a>
                        </template>
                        <template x-if="selected.admin_url">
                            <a :href="selected.admin_url" class="inline-flex items-center justify-center rounded-xl bg-white/10 text-white font-medium px-6 py-3 border border-white/30 hover:bg-white/20 transition">
                                Panel de administración
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if($events->hasPages())
        <div class="mt-10 flex justify-center [&_.bg-white]:bg-black/60 [&_.text-slate-700]:text-white [&_a]:text-[#e50914] [&_a:hover]:text-red-400">{{ $events->links() }}</div>
    @endif
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.novaTrack !== 'function') {
        return;
    }

    const eventCards = Array.from(document.querySelectorAll('[data-event-id]'));
    eventCards.forEach(function (card) {
        const eventId = parseInt(card.getAttribute('data-event-id') || '', 10);
        if (Number.isFinite(eventId) && eventId > 0) {
            window.novaTrack('view_event', { event_id: eventId });
        }
    });
});
</script>
@endpush
