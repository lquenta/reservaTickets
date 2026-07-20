@props([
    'href' => null,
    'icon' => '📌',
    'title',
    'description' => '',
    'button' => 'Abrir',
    'method' => null,
    'confirm' => null,
    'danger' => false,
])

<div class="rounded-2xl border-2 {{ $danger ? 'border-red-300/60 dark:border-fuchsia-800/50' : 'border-violet-200/60 dark:border-violet-700/50' }} bg-white dark:bg-slate-800/80 p-5 flex flex-col gap-3">
    <div class="flex items-start gap-3">
        <span class="text-2xl shrink-0" aria-hidden="true">{{ $icon }}</span>
        <div>
            <h3 class="font-semibold text-slate-800 dark:text-white">{{ $title }}</h3>
            @if($description)
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ $description }}</p>
            @endif
        </div>
    </div>
    @if($method)
        <form method="POST" action="{{ $href }}" @if($confirm) onsubmit="return confirm(@js($confirm));" @endif class="mt-auto">
            @csrf
            @method($method)
            <button type="submit" class="w-full rounded-xl px-4 py-2 text-sm font-semibold transition {{ $danger ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-200 hover:bg-violet-200 dark:hover:bg-violet-900/60' }}">
                {{ $button }}
            </button>
        </form>
    @elseif($href)
        <a href="{{ $href }}" class="mt-auto inline-block text-center rounded-xl px-4 py-2 text-sm font-semibold transition {{ $danger ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-violet-600 hover:bg-violet-700 text-white' }}">
            {{ $button }}
        </a>
    @endif
    {{ $slot ?? '' }}
</div>
