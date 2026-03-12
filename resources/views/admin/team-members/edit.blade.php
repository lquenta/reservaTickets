@extends('layouts.admin')

@section('title', 'Editar integrante - Admin')

@section('admin')
<div class="mb-8">
    <a href="{{ route('admin.team-members.index') }}" class="text-white/70 hover:text-[#e50914] text-sm transition">← Integrantes</a>
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mt-2">Editar integrante</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">{{ $team_member->name ?: 'Sin nombre' }}</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-lg">
    <form action="{{ route('admin.team-members.update', $team_member) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre (opcional)</label>
            <input type="text" name="name" id="name" value="{{ old('name', $team_member->name) }}" maxlength="255"
                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            @error('name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="role" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Rol o descripción del artista (opcional)</label>
            <input type="text" name="role" id="role" value="{{ old('role', $team_member->role) }}" maxlength="255"
                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            @error('role')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Foto actual</p>
            <div class="w-32 h-32 rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-700 ring-2 ring-slate-300 dark:ring-slate-600">
                <img src="{{ asset('storage/'.$team_member->photo_path) }}" alt="{{ $team_member->name }}" class="w-full h-full object-cover">
            </div>
            <label for="photo" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mt-3 mb-1">Cambiar foto (opcional, JPG/PNG/WebP, máx. 5 MB)</label>
            <input type="file" name="photo" id="photo" accept="image/*"
                class="block w-full text-sm text-slate-600 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-violet-100 file:text-violet-700 dark:file:bg-violet-900/50 dark:file:text-violet-300">
            @error('photo')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-[#e50914] hover:bg-red-600 px-5 py-2.5 text-white font-semibold transition">
                Guardar cambios
            </button>
            <a href="{{ route('admin.team-members.index') }}" class="rounded-xl border border-slate-300 dark:border-slate-600 px-5 py-2.5 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
