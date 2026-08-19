@extends('layouts.admin')

@section('title', 'Videos Facebook - Admin')

@section('admin')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Videos de Facebook</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Hasta {{ \App\Models\FeaturedVideo::MAX_ON_HOME }} videos en la sección «Videos» de la portada. El visitante elige cuál reproducir.</p>
    </div>
    @if($videos->count() < \App\Models\FeaturedVideo::MAX_ON_HOME)
        <a href="{{ route('admin.featured-videos.create') }}" class="rounded-xl bg-[#ff2daa] hover:bg-pink-600 px-5 py-2.5 text-white font-semibold transition">
            + Añadir video
        </a>
    @endif
</div>

<div class="rounded-2xl border border-amber-300/70 dark:border-amber-600/50 bg-amber-50 dark:bg-amber-900/20 px-5 py-4 mb-6 text-sm text-amber-900 dark:text-amber-100">
    <p class="font-semibold mb-1">Qué URL pegar</p>
    <p>Usa el enlace del reel, no el de Compartir. Ejemplo válido: <code class="text-xs break-all">https://www.facebook.com/reel/1051618684397981</code></p>
    <p class="mt-1 text-amber-800/80 dark:text-amber-200/80">No uses <code class="text-xs">facebook.com/share/r/…</code>. Abre el video en Facebook y copia la barra de direcciones.</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Listado (orden de aparición)</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Arrastra las filas para cambiar el orden. El primero es el video que se reproduce al entrar.</p>
    </div>
    <div class="p-6" id="featured-videos-sortable">
        @forelse($videos as $video)
            <div class="flex items-center gap-4 py-4 border-b border-slate-200 dark:border-slate-700 last:border-0 bg-white dark:bg-slate-800/80 rounded-lg px-3 sortable-item" data-video-id="{{ $video->id }}">
                <div class="sortable-handle shrink-0 text-slate-400 dark:text-slate-500 cursor-grab active:cursor-grabbing" aria-hidden="true" title="Arrastrar para reordenar">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6h2v2H8V6zm0 5h2v2H8v-2zm0 5h2v2H8v-2zm5-10h2v2h-2V6zm0 5h2v2h-2v-2zm0 5h2v2h-2v-2zm5-10h2v2h-2V6zm0 5h2v2h-2v-2zm0 5h2v2h-2v-2z"/></svg>
                </div>
                <div class="w-28 aspect-video rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-700 shrink-0 ring-2 ring-slate-300 dark:ring-slate-600 relative">
                    @if($video->thumbnail_path)
                        <img src="{{ $video->thumbnailUrl() }}" alt="{{ $video->title ?: 'Video' }}" class="w-full h-full object-cover" draggable="false">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-slate-400">
                            <x-icon name="play" class="w-8 h-8" />
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-800 dark:text-white">{{ $video->title ?: 'Sin título' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate" title="{{ $video->video_url }}">{{ $video->video_url }}</p>
                    <p class="text-xs mt-1">
                        @if($video->is_active)
                            <span class="inline-flex rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200 px-2 py-0.5 font-medium">Visible</span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-0.5 font-medium">Oculto</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('admin.featured-videos.edit', $video) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition">Editar</a>
                    <form action="{{ route('admin.featured-videos.destroy', $video) }}" method="POST" onsubmit="return confirm('¿Eliminar este video de la portada?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 transition">Eliminar</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="py-12 text-center text-slate-500 dark:text-slate-400">No hay videos. <a href="{{ route('admin.featured-videos.create') }}" class="text-[#ff2daa] hover:underline">Añade el primero</a> para que aparezcan en la portada.</p>
        @endforelse
    </div>
</div>

<form id="featured-videos-reorder-form" action="{{ route('admin.featured-videos.reorder') }}" method="POST" class="hidden">
    @csrf
    @method('PATCH')
</form>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('featured-videos-sortable');
    if (!el || !el.querySelector('.sortable-item')) return;

    var form = document.getElementById('featured-videos-reorder-form');
    if (!form) return;

    new Sortable(el, {
        animation: 150,
        handle: '.sortable-handle',
        ghostClass: 'opacity-50',
        onEnd: function() {
            var items = el.querySelectorAll('.sortable-item');
            var order = Array.from(items).map(function(item) { return item.getAttribute('data-video-id'); });
            form.querySelectorAll('input[name="order[]"]').forEach(function(input) { input.remove(); });
            order.forEach(function(id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order[]';
                input.value = id;
                form.appendChild(input);
            });
            form.submit();
        }
    });
});
</script>
@endpush
@endsection
