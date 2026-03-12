@extends('layouts.admin')

@section('title', 'Quiénes somos - Admin')

@section('admin')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Quiénes somos</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">Texto que se muestra en la sección «Quiénes somos» de la portada.</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-lg">
    <form action="{{ route('admin.site-content.update-quienes-somos') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        <div>
            <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Título de la sección</label>
            <input type="text" name="title" id="title" value="{{ old('title', $block->title) }}" required maxlength="255"
                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                placeholder="QUIÉNES SOMOS">
            @error('title')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="content" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contenido (párrafos separados por línea en blanco)</label>
            <textarea name="content" id="content" rows="8" required maxlength="10000"
                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 resize-y"
                placeholder="NOVA es tu plataforma...">{{ old('content', $block->content) }}</textarea>
            @error('content')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Se mostrará en la portada. Puedes usar varias líneas; se respetarán los párrafos.</p>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-500 px-5 py-2.5 text-white font-semibold transition">
                Guardar cambios
            </button>
            <a href="{{ route('admin.team-members.index') }}" class="rounded-xl border border-violet-500/60 text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/20 px-5 py-2.5 font-medium transition">
                Gestionar fotos de integrantes (slider)
            </a>
        </div>
    </form>
</div>
@endsection
