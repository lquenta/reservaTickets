<section id="galeria" class="mb-10">
    <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-1">Galería del archivo</h2>
    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Fotos y videos de este evento. Cuando la fecha ya pasó, aparece en el slider de Eventos pasados del inicio y esta galería se abre al entrar.</p>

    @if($event->media->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-6">
            @foreach($event->media as $item)
                <div class="rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden">
                    <div class="aspect-[4/3] bg-slate-900 bg-cover bg-center relative"
                         @if($item->thumbnailUrl()) style="background-image: url('{{ $item->thumbnailUrl() }}')" @endif>
                        @unless($item->thumbnailUrl())
                            <div class="absolute inset-0 bg-gradient-to-br from-violet-950 to-fuchsia-900/40"></div>
                        @endunless
                        @if($item->isVideo())
                            <span class="absolute inset-0 flex items-center justify-center text-white text-2xl">▶</span>
                        @endif
                    </div>
                    <div class="p-3">
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $item->sourceLabel() }}</p>
                        <form action="{{ route('admin.events.media.destroy', [$event, $item]) }}" method="POST" class="mt-2" onsubmit="return confirm('¿Quitar esta pieza de la galería?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline">Eliminar</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Todavía no hay piezas. Sube una foto o un video, o pega un enlace de Facebook.</p>
    @endif

    <div class="grid lg:grid-cols-3 gap-4">
        <form action="{{ route('admin.events.media.store', $event) }}" method="POST" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800/80 p-4 space-y-3">
            @csrf
            <input type="hidden" name="mode" value="photo">
            <h3 class="font-semibold text-slate-800 dark:text-white">Subir foto</h3>
            <input type="file" name="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 dark:text-slate-300">
            <p class="text-xs text-slate-500">JPG, PNG o WebP. Máximo 5 MB.</p>
            @error('file')
                @if(old('mode') === 'photo')<p class="text-sm text-red-600">{{ $message }}</p>@endif
            @enderror
            <button type="submit" class="rounded-xl bg-[#ff2daa] hover:bg-pink-600 px-4 py-2 text-white text-sm font-semibold">Añadir foto</button>
        </form>

        <form action="{{ route('admin.events.media.store', $event) }}" method="POST" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800/80 p-4 space-y-3">
            @csrf
            <input type="hidden" name="mode" value="video">
            <h3 class="font-semibold text-slate-800 dark:text-white">Subir video</h3>
            <input type="file" name="file" accept="video/mp4,video/webm" class="block w-full text-sm text-slate-600 dark:text-slate-300">
            <p class="text-xs text-slate-500">MP4 o WebM. Máximo 20 MB.</p>
            @error('file')
                @if(old('mode') === 'video')<p class="text-sm text-red-600">{{ $message }}</p>@endif
            @enderror
            <button type="submit" class="rounded-xl bg-[#ff2daa] hover:bg-pink-600 px-4 py-2 text-white text-sm font-semibold">Añadir video</button>
        </form>

        <form action="{{ route('admin.events.media.store', $event) }}" method="POST" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800/80 p-4 space-y-3">
            @csrf
            <input type="hidden" name="mode" value="facebook">
            <h3 class="font-semibold text-slate-800 dark:text-white">Enlace de Facebook</h3>
            <input type="url" name="external_url" value="{{ old('mode') === 'facebook' ? old('external_url') : '' }}" placeholder="https://www.facebook.com/reel/… o enlace de foto" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
            <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 dark:text-slate-300">
            <p class="text-xs text-slate-500">Reel, video o foto pública. La miniatura es opcional y se usa en la galería.</p>
            @error('external_url')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            @error('thumbnail')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button type="submit" class="rounded-xl bg-[#ff2daa] hover:bg-pink-600 px-4 py-2 text-white text-sm font-semibold">Añadir enlace</button>
        </form>
    </div>
</section>
