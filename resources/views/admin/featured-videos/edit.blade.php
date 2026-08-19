@extends('layouts.admin')

@section('title', 'Editar video - Admin')

@section('admin')
<div class="mb-8">
    <a href="{{ route('admin.featured-videos.index') }}" class="text-white/70 hover:text-[#ff2daa] text-sm transition">← Videos</a>
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mt-2">Editar video</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">{{ $video->title ?: 'Sin título' }}</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-lg">
    <form action="{{ route('admin.featured-videos.update', $video) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        <div>
            <label for="video_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL del reel o video *</label>
            <input type="url" name="video_url" id="video_url" value="{{ old('video_url', $video->video_url) }}" required maxlength="500"
                placeholder="https://www.facebook.com/reel/1051618684397981"
                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Copia la URL de la barra de direcciones al abrir el reel. No uses el enlace de Compartir (<code>facebook.com/share/…</code>).</p>
            @error('video_url')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Título (opcional)</label>
            <input type="text" name="title" id="title" value="{{ old('title', $video->title) }}" maxlength="255"
                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            @error('title')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Miniatura actual</p>
            <div class="w-48 aspect-video rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-700 ring-2 ring-slate-300 dark:ring-slate-600">
                @if($video->thumbnail_path)
                    <img src="{{ $video->thumbnailUrl() }}" alt="{{ $video->title ?: 'Video' }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-slate-400">
                        <x-icon name="play" class="w-10 h-10" />
                    </div>
                @endif
            </div>
            <label for="thumbnail" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mt-3 mb-1">Cambiar miniatura (opcional, JPG/PNG/WebP, máx. 5 MB)</label>
            <input type="file" name="thumbnail" id="thumbnail" accept="image/jpeg,image/png,image/webp"
                class="block w-full text-sm text-slate-600 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-violet-100 file:text-violet-700 dark:file:bg-violet-900/50 dark:file:text-violet-300">
            @error('thumbnail')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center gap-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $video->is_active ? '1' : '0') ? 'checked' : '' }}
                class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
            <label for="is_active" class="text-sm font-medium text-slate-700 dark:text-slate-300">Mostrar en la página principal</label>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-[#ff2daa] hover:bg-pink-600 px-5 py-2.5 text-white font-semibold transition">
                Guardar cambios
            </button>
            <a href="{{ route('admin.featured-videos.index') }}" class="rounded-xl border border-slate-300 dark:border-slate-600 px-5 py-2.5 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
