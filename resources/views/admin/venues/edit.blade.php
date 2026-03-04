@extends('layouts.admin')

@section('title', 'Editar lugar - Admin')

@section('admin')
<div class="max-w-2xl">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2">Editar lugar</h1>
    <p class="text-slate-600 dark:text-slate-400 mb-8">{{ $venue->name }}</p>
    <form method="POST" action="{{ route('admin.venues.update', $venue) }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-8 shadow-xl">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre</label>
            <input id="name" type="text" name="name" value="{{ old('name', $venue->name) }}" required maxlength="255"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="slug" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Slug (opcional)</label>
            <input id="slug" type="text" name="slug" value="{{ old('slug', $venue->slug) }}" maxlength="255"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="address" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Dirección (opcional)</label>
            <input id="address" type="text" name="address" value="{{ old('address', $venue->address) }}" maxlength="500"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="seat_rows" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Filas</label>
                <input id="seat_rows" type="number" name="seat_rows" value="{{ old('seat_rows', $venue->seat_rows) }}" min="1" max="50" required
                       class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
                @error('seat_rows')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="seat_columns" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Columnas</label>
                <input id="seat_columns" type="number" name="seat_columns" value="{{ old('seat_columns', $venue->seat_columns) }}" min="1" max="50" required
                       class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
                @error('seat_columns')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Actualmente {{ $venue->seats()->count() }} butacas. Al guardar se ajustarán al nuevo grid. Las filas son letras (A, B, C…) y las columnas números (1, 2, 3…).</p>

        <div>
            <label for="plan_image" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Imagen del plano (opcional, reemplaza la actual)</label>
            @if($venue->plan_image_path)
                <p class="text-sm text-slate-500 mb-1">Actual: <img src="{{ asset('storage/'.$venue->plan_image_path) }}" alt="Plano" class="inline-block h-20 rounded object-contain mt-1"></p>
            @endif
            <input id="plan_image" type="file" name="plan_image" accept="image/*"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('plan_image')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex gap-4">
            <button type="submit" class="rounded-lg bg-gradient-to-r from-violet-600 to-fuchsia-600 px-6 py-2 text-white font-medium">Guardar</button>
            <a href="{{ route('admin.venues.index') }}" class="rounded-lg border border-slate-300 dark:border-slate-600 px-6 py-2 text-slate-700 dark:text-slate-300">Cancelar</a>
        </div>
    </form>
</div>
@endsection
