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
            <div class="absolute inset-0 bg-gradient-to-b from-black via-[#180a2e] to-black z-0"></div>
        @endif
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_0%,rgba(255,45,170,0.15),transparent),radial-gradient(ellipse_50%_40%_at_80%_20%,rgba(57,255,20,0.08),transparent)] z-[1]" aria-hidden="true"></div>
        <div class="relative z-10 text-center px-4 max-w-4xl mx-auto" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) { visible = true; o.disconnect() } }, { threshold: 0.1 }); o.observe($el)">
            <h1 class="font-display text-5xl sm:text-7xl md:text-8xl lg:text-9xl tracking-widest text-[#ff2daa] animate-flicker text-glow st-glow-title mb-6"
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
                class="inline-block mt-8 px-10 py-4 font-bold tracking-widest text-lg rounded border-2 border-transparent btn-nova-primary st-glow-btn transition-all duration-300"
                x-show="visible"
                x-transition:enter="transition ease-out duration-700 delay-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0">
                {{ $hero_content?->content ?? 'ENTRAR' }}
            </a>
        </div>
    </section>

    @if(isset($featured_videos) && $featured_videos->isNotEmpty())
    {{-- Videos de Facebook --}}
    @php
        $videoPlayerPayload = $featured_videos->map(fn ($v) => [
            'title' => $v->title ?: '',
            'embed' => $v->embedUrl(),
            'thumb' => $v->thumbnailUrl(),
        ])->values()->all();
    @endphp
    <section id="videos" class="relative py-12 md:py-20 px-4 section-stranger-bg"
             x-data="Object.assign({ visible: false }, facebookVideoPlayer({{ \Illuminate\Support\Js::from($videoPlayerPayload) }}))"
             x-init="initPlayer(); const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.05 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 w-full max-w-5xl mx-auto transition duration-700 ease-out"
             :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
            <h2 class="font-display text-4xl sm:text-5xl md:text-6xl tracking-widest text-[#ff2daa] text-center mb-6 md:mb-10 st-glow-title">
                VIDEOS
            </h2>

            <div class="flex justify-center">
                <div x-ref="stage"
                     class="w-full overflow-hidden rounded-lg border border-purple-900/50 bg-black shadow-[0_0_40px_rgba(255,45,170,0.12)]"
                     style="max-width: 400px; aspect-ratio: 9 / 16; position: relative;">
                    <iframe src="{{ $featured_videos->first()?->embedUrl(360, 640) }}"
                            :src="embedSrc()"
                            :width="pluginWidth"
                            :height="pluginHeight"
                            style="position:absolute;inset:0;width:100%;height:100%;border:0;display:block;overflow:hidden"
                            scrolling="no"
                            allowfullscreen="true"
                            allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                            title="Video de Facebook"></iframe>
                </div>
            </div>

            <div class="grid gap-3 mt-6 mx-auto" x-show="videos.length > 1" x-cloak
                 style="max-width: 400px;"
                 :class="videos.length === 2 ? 'grid-cols-2' : 'grid-cols-3'">
                <template x-for="(v, i) in videos" :key="i">
                    <button type="button"
                            @click="active = i"
                            class="group relative overflow-hidden rounded-lg border bg-black/80 text-left transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#ff2daa]/50"
                            :class="active === i ? 'border-[#ff2daa] shadow-[0_0_20px_rgba(255,45,170,0.2)]' : 'border-purple-900/50 hover:border-[#ff2daa]/50'">
                        <div class="relative bg-gradient-to-br from-[#180a2e] to-[#ff2daa]/20" style="aspect-ratio: 9 / 16">
                            <img x-show="v.thumb" x-cloak :src="v.thumb" :alt="v.title || 'Video'" class="absolute inset-0 w-full h-full object-cover" draggable="false">
                            <div class="absolute inset-0 bg-black/35 group-hover:bg-black/20 transition"></div>
                            <span class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <span class="flex h-11 w-11 items-center justify-center rounded-full border-2 transition"
                                      :class="active === i ? 'border-[#ff2daa] bg-[#ff2daa]/20 text-[#ff2daa]' : 'border-white/80 bg-black/50 text-white'">
                                    <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                </span>
                            </span>
                        </div>
                        <p class="px-3 py-2 text-sm font-medium truncate" :class="active === i ? 'text-[#ff2daa]' : 'text-white/80'" x-text="v.title || ('Video ' + (i + 1))"></p>
                    </button>
                </template>
            </div>
        </div>
    </section>
    @endif

    {{-- Quiénes somos: contenido editable por admin + slider integrantes --}}
    <section id="quienes-somos" class="relative min-h-screen flex flex-col items-center justify-center py-20 px-4 section-stranger-bg" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.1 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 w-full max-w-6xl mx-auto"
            x-show="visible"
            x-transition:enter="transition ease-out duration-700"
            x-transition:enter-start="opacity-0 translate-y-8"
            x-transition:enter-end="opacity-100 translate-y-0">
            <h2 class="font-display text-4xl sm:text-5xl md:text-6xl tracking-widest text-[#ff2daa] mb-8 text-center st-glow-title">{{ $quienes_somos?->title ?? 'QUIÉNES SOMOS' }}</h2>
            <div class="max-w-3xl mx-auto text-center mb-8">
                <div class="text-white/90 text-lg leading-relaxed space-y-4 text-left">
                    @if($quienes_somos && $quienes_somos->content)
                        @foreach(explode("\n\n", $quienes_somos->content) as $paragraph)
                            @if(trim($paragraph))
                                <p class="text-white/90">{{ trim($paragraph) }}</p>
                            @endif
                        @endforeach
                    @else
                        <p class="text-white/90">NOVA es tu plataforma para descubrir eventos y reservar tickets de forma rápida y segura. Conectamos organizadores con el público: elige tu evento, reserva de 1 a 10 entradas con un código único de pago y recibe tus tickets por correo.</p>
                        <p class="text-white/70 text-base">Simple, transparente y pensado para que no te pierdas nada.</p>
                    @endif
                </div>
            </div>

            @if($team_members->isNotEmpty())
            <div class="mt-6 w-full"
                 x-data="{ modalOpen: false, activeIndex: 0 }"
                 x-effect="document.body.style.overflow = modalOpen ? 'hidden' : ''">
                {{-- Franja horizontal: en mobile (<768px) scroll horizontal con tarjetas anchas; en desktop igual que antes --}}
                <div class="flex flex-nowrap min-h-[400px] bg-black/30 overflow-x-auto overflow-y-hidden md:overflow-hidden scroll-snap-x md:scroll-snap-none scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" style="width: 100vw; margin-left: calc(50% - 50vw); height: 100vh;">
                    <div class="flex flex-nowrap h-full min-w-full md:min-w-0 w-max md:w-full">
                    @foreach($team_members as $i => $member)
                        <button type="button"
                                class="flex-none w-[78vw] min-w-[78vw] h-full relative overflow-hidden text-left focus:outline-none focus:ring-2 focus:ring-[#39ff14]/50 focus:ring-inset cursor-pointer scroll-snap-center shrink-0 md:flex-1 md:min-w-0 md:w-auto"
                                @click.prevent="activeIndex = {{ $i }}; modalOpen = true">
                            <img src="{{ asset('storage/'.$member->photo_path) }}"
                                 alt="{{ $member->name ?: 'Integrante' }}"
                                 class="absolute inset-0 w-full h-full object-cover object-center">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 from-30% to-transparent pointer-events-none"></div>
                            @if($member->name || $member->role)
                                <div class="absolute bottom-0 left-0 right-0 z-10 px-2 py-2 sm:px-3 sm:py-3">
                                    @if($member->name)
                                        <p class="font-semibold text-white text-xs sm:text-sm truncate">{{ $member->name }}</p>
                                    @endif
                                    @if($member->role)
                                        <p class="text-[#ff2daa] text-xs mt-0.5 truncate">{{ $member->role }}</p>
                                    @endif
                                </div>
                            @endif
                        </button>
                    @endforeach
                    </div>
                </div>

                {{-- Modal: imagen al hacer clic; clic fuera cierra; sin desplazar la página --}}
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/95 overflow-y-auto overscroll-contain"
                     x-show="modalOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click.self="modalOpen = false"
                     @keydown.escape.window="modalOpen = false"
                     role="dialog"
                     aria-modal="true"
                     aria-label="Ver imagen"
                     x-cloak
                     style="display: none;">
                    <button type="button" @click="modalOpen = false" class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors" aria-label="Cerrar">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    @foreach($team_members as $i => $member)
                        <div class="flex flex-col items-center shrink-0"
                             x-show="modalOpen && activeIndex === {{ $i }}"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             @click.stop>
                            <img src="{{ asset('storage/'.$member->photo_path) }}"
                                 alt="{{ $member->name ?: 'Integrante' }}"
                                 class="max-w-[85vw] max-h-[65vh] w-auto h-auto object-contain rounded-lg shadow-2xl">
                            @if($member->name || $member->role)
                                <div class="mt-3 text-center">
                                    @if($member->name)
                                        <p class="font-semibold text-white text-base sm:text-lg">{{ $member->name }}</p>
                                    @endif
                                    @if($member->role)
                                        <p class="text-[#ff2daa] text-sm mt-0.5">{{ $member->role }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </section>

    {{-- Nuestros eventos --}}
    <section id="nuestros-eventos" class="relative min-h-screen flex flex-col items-center justify-center py-20 px-4 section-stranger-bg" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.1 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 w-full max-w-6xl mx-auto" x-show="visible" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
            <h2 class="font-display text-4xl sm:text-5xl md:text-6xl tracking-widest text-[#ff2daa] text-center mb-12 st-glow-title">
                NUESTROS EVENTOS
            </h2>

            @if($featured_events->isNotEmpty())
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3 mb-12">
                    @foreach($featured_events as $event)
                        @php
                            $featuredCanReserve = $event->acceptsReservations();
                            $featuredSalesPaused = $event->sales_paused && $event->is_active;
                        @endphp
                        <article class="group relative overflow-hidden rounded-lg border border-purple-900/50 bg-black/80 backdrop-blur hover:border-[#39ff14]/50 transition-all duration-300" @if($featuredSalesPaused) x-data="{ showContactInfo: false }" @endif>
                            <div class="aspect-[4/3] bg-cover bg-center relative @if(!$event->cover_image_path) bg-gradient-to-br from-[#180a2e] to-[#ff2daa]/20 @endif"
                                @if($event->cover_image_path) style="background-image: url('{{ asset('storage/'.$event->cover_image_path) }}');" @endif>
                                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
                                <span class="nova-accent-chip absolute top-3 left-3 z-[1] rounded px-2 py-0.5 text-[10px] uppercase">Destacado</span>
                            </div>
                            <div class="p-5">
                                <h3 class="text-xl font-bold text-white mb-2 flex items-center gap-2 flex-wrap">
                                    {{ $event->name }}
                                    @if($event->isPresaleActive())
                                        <span class="inline-flex rounded-full bg-[#39ff14]/90 text-[#0d061b] px-2 py-0.5 text-xs font-bold uppercase tracking-wider">Preventa</span>
                                    @endif
                                    @if($featuredSalesPaused)
                                        <span class="inline-flex rounded-full bg-amber-600/90 text-white px-2 py-0.5 text-xs font-bold">VENTAS PAUSADAS</span>
                                    @endif
                                </h3>
                                <p class="text-white/70 text-sm mb-2">{{ $event->starts_at->translatedFormat('l d F Y, H:i') }}</p>
                                <p class="text-white/60 text-sm mb-4">{{ $event->venue }}</p>
                                @auth
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('admin.dashboard') }}" class="inline-block text-sm text-white/70 hover:text-[#39ff14] transition">Panel admin</a>
                                    @elseif($featuredSalesPaused)
                                        <button type="button" @click="showContactInfo = !showContactInfo" class="inline-block text-sm font-semibold text-amber-400 hover:text-amber-300 transition text-left">Más info</button>
                                        <p x-show="showContactInfo" x-cloak class="text-amber-200/90 text-sm mt-2">Favor comunicarse al {{ \App\Models\Event::SALES_CONTACT_PHONE }} para más información</p>
                                    @elseif($featuredCanReserve)
                                        <a href="{{ route('reservations.create', $event) }}" class="inline-block text-sm font-semibold text-[#ff2daa] hover:text-[#39ff14] transition">Reservar →</a>
                                    @endif
                                @else
                                    @if($featuredSalesPaused)
                                        <button type="button" @click="showContactInfo = !showContactInfo" class="inline-block text-sm font-semibold text-amber-400 hover:text-amber-300 transition text-left">Más info</button>
                                        <p x-show="showContactInfo" x-cloak class="text-amber-200/90 text-sm mt-2">Favor comunicarse al {{ \App\Models\Event::SALES_CONTACT_PHONE }} para más información</p>
                                    @else
                                        <a href="{{ route('reservations.entry', $event) }}" class="inline-block text-sm font-semibold text-[#ff2daa] hover:text-[#39ff14] transition">Reservar →</a>
                                    @endif
                                @endauth
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <div class="text-center">
                <a href="{{ route('events.index') }}" class="inline-block px-8 py-3 font-bold tracking-widest rounded transition st-glow-btn btn-nova-primary">
                    VER TODOS LOS EVENTOS
                </a>
            </div>
        </div>
    </section>

    @if(isset($past_events) && $past_events->isNotEmpty())
    <section id="eventos-pasados" class="relative py-20 px-4 section-stranger-bg"
             x-data="{
                visible: false,
                scroll(dir) {
                    const el = this.$refs.track;
                    const card = el && el.querySelector('article');
                    const step = card ? card.getBoundingClientRect().width + 32 : 320;
                    el.scrollBy({ left: dir * step, behavior: 'smooth' });
                }
             }"
             x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.08 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 w-full max-w-6xl mx-auto" x-show="visible" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
            <h2 class="font-display text-4xl sm:text-5xl md:text-6xl tracking-widest text-[#ff2daa] text-center mb-4 st-glow-title">
                EVENTOS PASADOS
            </h2>
            <p class="text-white/70 text-center mb-8 max-w-xl mx-auto">Recuerdos del más reciente al más antiguo.</p>
            <div class="flex justify-end gap-2 mb-4">
                <button type="button" @click="scroll(-1)" class="w-10 h-10 rounded-full border border-[#ff2daa]/50 bg-black/50 text-white hover:border-[#39ff14] hover:text-[#39ff14] transition" aria-label="Anterior">‹</button>
                <button type="button" @click="scroll(1)" class="w-10 h-10 rounded-full border border-[#ff2daa]/50 bg-black/50 text-white hover:border-[#39ff14] hover:text-[#39ff14] transition" aria-label="Siguiente">›</button>
            </div>
            <div x-ref="track" class="flex gap-8 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach($past_events as $event)
                    @php
                        $photos = (int) ($event->photos_count ?? 0);
                        $videos = (int) ($event->videos_count ?? 0);
                    @endphp
                    <article class="snap-start shrink-0 w-[85%] sm:w-[calc(50%-16px)] lg:w-[calc(33.333%-22px)] group relative overflow-hidden rounded-lg border border-purple-900/50 bg-black/80 backdrop-blur hover:border-[#39ff14]/50 transition-all duration-300">
                        <a href="{{ route('events.gallery', $event) }}" class="block">
                            <div class="aspect-[4/3] bg-cover bg-center relative @if(!$event->cover_image_path) bg-gradient-to-br from-[#180a2e] to-[#ff2daa]/20 @endif"
                                @if($event->cover_image_path) style="background-image: url('{{ asset('storage/'.$event->cover_image_path) }}');" @endif>
                                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
                                <span class="absolute top-3 left-3 z-[1] rounded border border-[#39ff14]/50 bg-black/60 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-[#39ff14]">Archivo</span>
                            </div>
                            <div class="p-5">
                                <h3 class="text-xl font-bold text-white mb-2">{{ $event->name }}</h3>
                                <p class="text-white/70 text-sm mb-2">{{ $event->starts_at->translatedFormat('l d F Y, H:i') }}</p>
                                <p class="text-white/60 text-sm mb-4">{{ $event->venue }}</p>
                                <span class="inline-block text-sm font-semibold text-[#ff2daa] group-hover:text-[#39ff14] transition">
                                    @if($photos === 0 && $videos === 0)
                                        Ver galería →
                                    @else
                                        {{ $photos }} {{ $photos === 1 ? 'foto' : 'fotos' }} · {{ $videos }} {{ $videos === 1 ? 'video' : 'videos' }} →
                                    @endif
                                </span>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Contáctenos --}}
    <section id="contacto" class="relative min-h-screen flex items-center justify-center py-20 px-4 section-stranger-bg" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.1 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 w-full max-w-2xl mx-auto" x-show="visible" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
            <h2 class="font-display text-3xl sm:text-4xl md:text-5xl tracking-wide sm:tracking-widest text-[#ff2daa] text-center mb-10 st-glow-title px-1">
                CONTÁCTENOS
            </h2>

            <p class="text-white/80 text-center mb-8">Escríbenos por WhatsApp y te respondemos a la brevedad.</p>

            <div class="text-center">
                <a href="https://wa.me/59164066996?text={{ rawurlencode('necesito ayuda por favor') }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center justify-center gap-3 px-8 py-4 font-bold tracking-widest rounded-xl bg-[#25D366] text-black hover:bg-[#1ebe5d] transition">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M20.5 3.5A11 11 0 0 0 2.1 17.2L1 23l6-1.6A11 11 0 0 0 20.5 3.5zM12 20.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3.5.9.9-3.4-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.5.1-.6.8-.7.9-.3.2-.5.1a6.7 6.7 0 0 1-2-1.2 7.4 7.4 0 0 1-1.4-1.7c-.1-.2 0-.4.1-.5l.4-.4.2-.3a1.7 1.7 0 0 0 .1-.4.5.5 0 0 0 0-.4c0-.1-.5-1.3-.7-1.7s-.4-.4-.5-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.4 3.9c.6.2 1.1.4 1.5.5a3.6 3.6 0 0 0 1.6.1 2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .2-1.2c-.1-.1-.2-.2-.4-.3z"/>
                    </svg>
                    Escribir por WhatsApp
                </a>
            </div>
        </div>
    </section>

    {{-- Boletín --}}
    <section id="boletin" class="relative min-h-screen flex items-center justify-center py-20 px-4 section-stranger-bg" x-data="{ visible: false }" x-init="const o = new IntersectionObserver(([e]) => { if (e.isIntersecting) visible = true }, { threshold: 0.1 }); o.observe($el)">
        <div class="section-stranger-bg__inner"></div>
        <div class="relative z-10 w-full max-w-xl mx-auto text-center" x-show="visible" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
            <h2 class="font-display text-4xl sm:text-5xl tracking-widest text-[#ff2daa] mb-4 st-glow-title">
                BOLETÍN
            </h2>
            <p class="text-white/80 mb-8">
                Suscríbete y recibe novedades y próximos eventos.
            </p>

            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                @csrf
                <input type="email" name="email" placeholder="tu@correo.com" required
                    class="flex-1 px-4 py-3 bg-black/60 border border-purple-900/50 rounded text-white placeholder-white/40 focus:border-[#39ff14] focus:ring-1 focus:ring-[#39ff14] outline-none transition">
                <button type="submit" class="px-8 py-3 font-bold tracking-widest rounded transition whitespace-nowrap st-glow-btn btn-nova-primary">
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
            <a href="{{ route('events.index') }}" class="inline-block px-10 py-4 font-bold tracking-widest rounded border-2 border-transparent btn-nova-primary st-glow-btn transition">
                RESERVAR ENTRADAS
            </a>
        </div>
    </section>
</div>

<script>
function homeReveal() {
    return { init() {} };
}
function facebookVideoPlayer(videos) {
    return {
        videos: Array.isArray(videos) ? videos : [],
        active: 0,
        pluginWidth: 360,
        pluginHeight: 640,
        initPlayer() {
            const apply = () => {
                const el = this.$refs.stage;
                if (!el) return;
                const w = Math.round(el.getBoundingClientRect().width);
                if (w >= 220 && w !== this.pluginWidth) {
                    this.pluginWidth = w;
                    this.pluginHeight = Math.round(w * 16 / 9);
                }
            };
            this.$nextTick(() => {
                apply();
                if (this.$refs.stage && typeof ResizeObserver !== 'undefined') {
                    new ResizeObserver(apply).observe(this.$refs.stage);
                }
            });
        },
        embedSrc() {
            const base = this.videos[this.active] && this.videos[this.active].embed;
            if (!base) return '';
            return base + '&width=' + this.pluginWidth + '&height=' + this.pluginHeight;
        }
    };
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
