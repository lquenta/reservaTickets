@extends('layouts.admin')

@section('title', 'Subir varios integrantes - Admin')

@section('admin')
<div class="mb-8">
    <a href="{{ route('admin.team-members.index') }}" class="text-white/70 hover:text-[#e50914] text-sm transition">← Integrantes</a>
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mt-2">Subir varios integrantes</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">Selecciona varias fotos a la vez (máx. 20). Nombre y rol se pueden editar después en cada uno.</p>
    <p class="text-slate-500 dark:text-slate-500 text-sm mt-1">Si sale error «POST too large», sube menos fotos por vez (ej. 5–10) o aumenta <code class="text-xs bg-slate-200 dark:bg-slate-700 px-1 rounded">post_max_size</code> en tu php.ini.</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-lg">
    <form action="{{ route('admin.team-members.bulk-store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <div>
            <label for="photos" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fotos * (JPG, PNG o WebP, máx. 5 MB cada una, hasta 20 a la vez)</label>
            <input type="file" name="photos[]" id="photos" accept="image/jpeg,image/png,image/webp" multiple required
                class="block w-full text-sm text-slate-600 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-violet-100 file:text-violet-700 dark:file:bg-violet-900/50 dark:file:text-violet-300">
            @error('photos')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @if($errors->has('photos.*'))
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $errors->first('photos.*') }}</p>
            @endif
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-[#e50914] hover:bg-red-600 px-5 py-2.5 text-white font-semibold transition">
                Subir todas
            </button>
            <a href="{{ route('admin.team-members.index') }}" class="rounded-xl border border-slate-300 dark:border-slate-600 px-5 py-2.5 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
