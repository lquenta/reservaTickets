@php
    $statusChips = [];
    if (!$event->is_active) {
        $statusChips[] = ['label' => 'Sold out', 'class' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300'];
    } elseif ($event->sales_paused) {
        $statusChips[] = ['label' => 'Pausado', 'class' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200'];
    } else {
        $statusChips[] = ['label' => 'Activo', 'class' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300'];
    }
@endphp
<article class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg flex flex-col">
    <div class="aspect-[16/9] bg-gradient-to-br from-violet-600 to-fuchsia-700 relative">
        @if($event->cover_image_path)
            <img src="{{ asset('storage/'.$event->cover_image_path) }}" alt="" class="absolute inset-0 w-full h-full object-cover">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
        <div class="absolute bottom-3 left-3 right-3">
            <h2 class="text-lg font-bold text-white line-clamp-2">{{ $event->name }}</h2>
            <p class="text-sm text-white/80">{{ $event->starts_at->translatedFormat('d M Y, H:i') }}</p>
        </div>
    </div>
    <div class="p-4 flex-1 flex flex-col gap-3">
        <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-1">{{ $event->venue }}</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach($statusChips as $chip)
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $chip['class'] }}">{{ $chip['label'] }}</span>
            @endforeach
            @if($event->venue_id)
                <span class="inline-flex rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 px-2.5 py-0.5 text-xs font-medium">Butacas</span>
            @else
                <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400 px-2.5 py-0.5 text-xs font-medium">Sin butacas</span>
            @endif
        </div>
        @if(isset($event->confirmed_count))
            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ $event->confirmed_count }} confirmadas
                @if($event->pending_count > 0)
                    · {{ $event->pending_count }} pendientes
                @endif
            </p>
        @endif
        <div class="mt-auto flex items-center gap-2 pt-2">
            <a href="{{ route('admin.events.show', $event) }}"
               class="flex-1 text-center rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-4 py-2.5 text-white text-sm font-semibold hover:shadow-lg transition">
                Gestionar evento
            </a>
            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" class="rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2.5 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" aria-label="Más opciones">⋮</button>
                <div x-show="open" @click.outside="open = false" x-cloak
                     class="absolute right-0 bottom-full mb-1 z-10 min-w-[140px] rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-xl py-1">
                    <a href="{{ route('admin.events.edit', $event) }}" @click="open = false" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">Editar</a>
                    <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('¿Eliminar este evento?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</article>
