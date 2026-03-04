@extends('layouts.admin')

@section('title', 'Nuevo evento - Admin')

@section('admin')
<div class="max-w-2xl">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2">Nuevo evento</h1>
    <p class="text-slate-600 dark:text-slate-400 mb-8">Completa los datos del evento.</p>
    <form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-8 shadow-xl">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2 @error('name') border-red-500 @enderror">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descripción</label>
            <textarea id="description" name="description" rows="3" maxlength="5000" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">{{ old('description') }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="starts_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fecha y hora</label>
            <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('starts_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="venue" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lugar (nombre o dirección)</label>
            <input id="venue" type="text" name="venue" value="{{ old('venue') }}" required maxlength="255"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('venue')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="venue_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sala / plano para reserva por butacas (opcional)</label>
            <select id="venue_id" name="venue_id" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
                <option value="">Sin reserva por butacas</option>
                @foreach($venues as $v)
                    <option value="{{ $v->id }}" {{ old('venue_id') == $v->id ? 'selected' : '' }}>{{ $v->name }} ({{ $v->seat_rows }}×{{ $v->seat_columns }})</option>
                @endforeach
            </select>
            @error('venue_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="payment_code_prefix" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Prefijo código de pago (opcional)</label>
            <input id="payment_code_prefix" type="text" name="payment_code_prefix" value="{{ old('payment_code_prefix') }}" maxlength="50"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
        </div>

        <div>
            <label for="cover_image" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Imagen de portada / arte del evento (opcional)</label>
            <input id="cover_image" type="file" name="cover_image" accept="image/*"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('cover_image')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="qr_image" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Imagen QR (opcional)</label>
            <input id="qr_image" type="file" name="qr_image" accept="image/*"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('qr_image')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center">
            <input id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                   class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
            <label for="is_active" class="ml-2 text-sm text-slate-700 dark:text-slate-300">Evento activo (visible en listado)</label>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="rounded-lg bg-gradient-to-r from-violet-600 to-fuchsia-600 px-6 py-2 text-white font-medium">Crear evento</button>
            <a href="{{ route('admin.events.index') }}" class="rounded-lg border border-slate-300 dark:border-slate-600 px-6 py-2 text-slate-700 dark:text-slate-300">Cancelar</a>
        </div>
    </form>
</div>
@endsection
