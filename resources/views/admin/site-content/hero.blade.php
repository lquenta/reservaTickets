@extends('layouts.admin')

@section('title', 'Texto Hero - Admin')

@section('admin')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Texto del Hero</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">Frase y botón de la sección inicial de la portada (debajo de NOVA).</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-lg">
    <form action="{{ route('admin.site-content.update-hero') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        <div>
            <label for="subtitle" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Frase (debajo de NOVA)</label>
            <input type="text" name="subtitle" id="subtitle" value="{{ old('subtitle', $block->title) }}" required maxlength="255"
                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                placeholder="Tus entradas. Tu experiencia.">
            @error('subtitle')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="cta_text" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Texto del botón</label>
            <input type="text" name="cta_text" id="cta_text" value="{{ old('cta_text', $block->content) }}" required maxlength="100"
                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                placeholder="ENTRAR">
            @error('cta_text')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-500 px-5 py-2.5 text-white font-semibold transition">
            Guardar cambios
        </button>
    </form>
</div>
@endsection
