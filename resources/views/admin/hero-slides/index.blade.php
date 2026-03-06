@extends('layouts.admin')

@section('title', 'Fondo inicio (Hero) - Admin')

@section('admin')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Fondo de la portada</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">Elige <strong>fotos (slider)</strong> o <strong>video</strong> para la sección inicial (NOVA · Tus entradas. Tu experiencia. · ENTRAR). Solo uno: si usas video no hay slider; si usas fotos no hay video.</p>
</div>

{{-- Selector: Fotos o Video --}}
<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-lg mb-8">
    <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Tipo de fondo</h2>
    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">
        @if($heroSetting->isVideo())
            Actualmente: <strong class="text-violet-600 dark:text-violet-400">Video</strong>
            @if($heroSetting->video_url)
                — Enlace: <span class="truncate inline-block max-w-md align-bottom" title="{{ $heroSetting->video_url }}">{{ $heroSetting->video_url }}</span>
            @else
                — Video subido
            @endif
        @else
            Actualmente: <strong class="text-violet-600 dark:text-violet-400">Fotos (slider)</strong>
        @endif
    </p>

    @if($heroSetting->isVideo())
        <form action="{{ route('admin.hero-slides.use-slider') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="rounded-xl bg-slate-600 hover:bg-slate-500 px-5 py-2.5 text-white font-semibold transition">
                Usar fotos (slider) en su lugar
            </button>
        </form>
    @else
        <div class="border-t border-slate-200 dark:border-slate-700 pt-6 mt-6">
            <h3 class="font-semibold text-slate-800 dark:text-white mb-3">Usar video como fondo</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Usa una <strong>URL directa</strong> al archivo de video (ej. .mp4 en tu servidor o CDN) o <strong>sube</strong> un archivo (MP4, WebM, OGG, máx. 100 MB). Para YouTube/Vimeo es mejor descargar el video y subirlo.</p>
            <form action="{{ route('admin.hero-slides.store-video') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="video_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL del video (enlace)</label>
                    <input type="url" name="video_url" id="video_url" value="{{ old('video_url') }}" placeholder="https://tudominio.com/video.mp4"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                    @error('video_url')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="video" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">O subir video (MP4, WebM, OGG — máx. 100 MB)</label>
                    <input type="file" name="video" id="video" accept="video/mp4,video/webm,video/ogg"
                        class="block w-full text-sm text-slate-600 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-violet-100 file:text-violet-700 dark:file:bg-violet-900/50 dark:file:text-violet-300">
                    @error('video')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-500 px-5 py-2.5 text-white font-semibold transition">
                    Guardar y usar video como fondo
                </button>
            </form>
        </div>
    @endif
</div>

@if($heroSetting->isSlider())
{{-- Fotos: añadir imagen --}}
<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-lg mb-8">
    <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Añadir imagen al slider</h2>
    <form action="{{ route('admin.hero-slides.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
        @csrf
        <div class="min-w-[200px]">
            <label for="image" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Imagen (JPG/PNG, máx. 5 MB)</label>
            <input type="file" name="image" id="image" accept="image/*" required
                class="block w-full text-sm text-slate-600 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-violet-100 file:text-violet-700 dark:file:bg-violet-900/50 dark:file:text-violet-300 hover:file:bg-violet-200 dark:hover:file:bg-violet-800/50">
            @error('image')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-500 px-5 py-2.5 text-white font-semibold transition">
            Subir y añadir al slider
        </button>
    </form>
</div>

{{-- Lista de imágenes --}}
<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Imágenes del slider (orden de aparición)</h2>
    </div>
    <div class="p-6">
        @forelse($slides as $slide)
            <div class="flex items-center gap-4 py-4 border-b border-slate-200 dark:border-slate-700 last:border-0">
                <div class="w-24 h-16 rounded-lg overflow-hidden bg-slate-200 dark:bg-slate-700 shrink-0">
                    <img src="{{ asset('storage/'.$slide->image_path) }}" alt="Slide {{ $slide->sort_order }}" class="w-full h-full object-cover">
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-slate-600 dark:text-slate-400">Orden {{ $slide->sort_order }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-500 truncate">{{ $slide->image_path }}</p>
                </div>
                <form action="{{ route('admin.hero-slides.destroy', $slide) }}" method="POST" class="shrink-0" onsubmit="return confirm('¿Eliminar esta imagen del slider?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 transition">Eliminar</button>
                </form>
            </div>
        @empty
            <p class="py-12 text-center text-slate-500 dark:text-slate-400">No hay imágenes. Sube la primera arriba. Si no hay ninguna, la portada usará el fondo por defecto (gradiente).</p>
        @endforelse
    </div>
</div>
@endif
@endsection
