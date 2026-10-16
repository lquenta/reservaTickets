@extends('layouts.app')

@section('title', $event->name.' — Galería')

@section('content')
@php
    $galleryItems = $event->media->map(fn ($item) => [
        'id' => $item->id,
        'kind' => $item->kind,
        'thumb' => $item->thumbnailUrl(),
        'file' => $item->fileUrl(),
        'embed' => $item->embedUrl(720, 1280),
        'label' => $item->sourceLabel(),
    ])->values();
@endphp

<div class="mb-8" x-data="{
        filter: 'all',
        light: null,
        items: {{ \Illuminate\Support\Js::from($galleryItems) }},
        visible(kind) { return this.filter === 'all' || this.filter === kind; },
        open(id) { this.light = this.items.find(item => item.id === id) || null; document.body.style.overflow = 'hidden'; },
        close() { this.light = null; document.body.style.overflow = ''; }
     }"
     @keydown.escape.window="close()">
    <a href="{{ route('home') }}#eventos-pasados" class="text-sm text-white/70 hover:text-[#ff2daa] transition">← Eventos pasados</a>
    <p class="mt-6 text-xs font-bold tracking-[0.28em] text-white/50">{{ $event->starts_at->translatedFormat('d M Y') }}</p>
    <h1 class="font-display text-4xl md:text-6xl text-[#ff2daa] tracking-widest st-glow-title mt-2">{{ $event->name }}</h1>
    <p class="text-white/70 mt-3">{{ $event->venue }} · galería de fotos y videos</p>

    <div class="flex flex-wrap gap-2 mt-6">
        <button type="button" @click="filter = 'all'" class="rounded-full px-4 py-1.5 text-sm font-bold border transition" :class="filter === 'all' ? 'bg-[#ff2daa] border-[#ff2daa] text-white' : 'border-[#ff2daa]/40 text-white/80'">Todo</button>
        <button type="button" @click="filter = 'photo'" class="rounded-full px-4 py-1.5 text-sm font-bold border transition" :class="filter === 'photo' ? 'bg-[#ff2daa] border-[#ff2daa] text-white' : 'border-[#ff2daa]/40 text-white/80'">Fotos</button>
        <button type="button" @click="filter = 'video'" class="rounded-full px-4 py-1.5 text-sm font-bold border transition" :class="filter === 'video' ? 'bg-[#ff2daa] border-[#ff2daa] text-white' : 'border-[#ff2daa]/40 text-white/80'">Videos</button>
    </div>

    @if($event->media->isEmpty())
        <div class="mt-10 rounded-2xl border border-purple-900/50 bg-black/60 p-12 text-center">
            <p class="text-white/80">Todavía no hay fotos ni videos de este evento.</p>
        </div>
    @else
        <div class="mt-8 columns-1 sm:columns-2 lg:columns-3 gap-4">
            @foreach($event->media as $item)
                <button type="button" x-show="visible('{{ $item->kind }}')" @click="open({{ $item->id }})"
                        class="mb-4 block w-full break-inside-avoid overflow-hidden rounded-xl border border-purple-900/50 bg-black text-left hover:border-[#39ff14]/50 transition">
                    <div class="relative bg-gradient-to-br from-[#180a2e] to-[#ff2daa]/20 {{ $item->isVideo() ? 'aspect-[9/16]' : 'aspect-[4/3]' }}">
                        @if($item->thumbnailUrl())
                            <img src="{{ $item->thumbnailUrl() }}" alt="{{ $item->sourceLabel() }}" class="absolute inset-0 h-full w-full object-cover">
                        @endif
                        <span class="absolute left-3 top-3 rounded-full border border-white/30 bg-black/60 px-2 py-0.5 text-[10px] font-bold tracking-wide">{{ $item->sourceLabel() }}</span>
                        @if($item->isVideo())
                            <span class="absolute inset-0 flex items-center justify-center">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white bg-black/50 text-white">
                                    <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </span>
                            </span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    @endif

    <div x-show="light" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 p-4" @click.self="close()" style="display: none;">
        <button type="button" @click="close()" class="absolute top-4 right-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white text-2xl" aria-label="Cerrar">×</button>
        <div class="w-full max-w-3xl" @click.stop>
            <template x-if="light && light.kind === 'photo' && light.file">
                <img :src="light.file" alt="" class="mx-auto max-h-[82vh] w-auto rounded-lg">
            </template>
            <template x-if="light && light.kind === 'video' && light.file">
                <video :src="light.file" controls autoplay class="mx-auto max-h-[82vh] w-full rounded-lg bg-black"></video>
            </template>
            <template x-if="light && light.embed">
                <div class="mx-auto overflow-hidden rounded-lg bg-black" :class="light.kind === 'video' ? 'max-w-[420px] aspect-[9/16]' : 'max-w-[560px] min-h-[420px]'">
                    <iframe :src="light.embed" class="h-full w-full min-h-[420px] border-0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen title="Facebook"></iframe>
                </div>
            </template>
            <p class="mt-3 text-center text-sm text-white/70" x-text="light ? light.label : ''"></p>
        </div>
    </div>
</div>
@endsection
