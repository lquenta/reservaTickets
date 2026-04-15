@extends('layouts.admin')

@section('title', 'Editar evento - Admin')

@section('admin')
<div class="max-w-2xl">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2">Editar evento</h1>
    <p class="text-slate-600 dark:text-slate-400 mb-8">{{ $event->name }}</p>
    <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data" class="space-y-5 rounded-3xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-8 shadow-xl">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre</label>
            <input id="name" type="text" name="name" value="{{ old('name', $event->name) }}" required maxlength="255"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descripción</label>
            <textarea id="description" name="description" rows="3" maxlength="5000" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">{{ old('description', $event->description) }}</textarea>
        </div>

        <div>
            <label for="starts_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fecha y hora</label>
            <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at->format('Y-m-d\TH:i')) }}" required
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('starts_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="venue" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lugar (nombre o dirección)</label>
            <input id="venue" type="text" name="venue" value="{{ old('venue', $event->venue) }}" required maxlength="255"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('venue')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="venue_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sala / plano para reserva por butacas (opcional)</label>
            <select id="venue_id" name="venue_id" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
                <option value="">Sin reserva por butacas</option>
                @foreach($venues as $v)
                    <option value="{{ $v->id }}" {{ old('venue_id', $event->venue_id) == $v->id ? 'selected' : '' }}>{{ $v->name }} ({{ $v->seat_rows }}×{{ $v->seat_columns }})</option>
                @endforeach
            </select>
            @error('venue_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        @php
            $selectedVenue = $venues->firstWhere('id', old('venue_id', $event->venue_id));
        @endphp
        @if($selectedVenue && $selectedVenue->sections->isNotEmpty())
        <div class="rounded-xl border-2 border-violet-200/60 dark:border-violet-700/50 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Plano del evento — Secciones</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Activa las secciones que estarán disponibles para este evento y opcionalmente asigna un precio por sección.</p>
            <div class="space-y-3">
                @php
                    $eventSectionIds = $event->sections->pluck('id')->flip();
                @endphp
                @foreach($selectedVenue->sections as $section)
                    @php
                        $pivot = $event->sections->firstWhere('id', $section->id);
                        $currentPrice = $pivot ? $pivot->pivot->price : null;
                        $isUsed = $pivot !== null || old("event_sections.{$section->id}.use");
                    @endphp
                    <div class="flex flex-wrap items-center gap-4 rounded-lg border border-slate-200 dark:border-slate-600 p-4 bg-slate-50/50 dark:bg-slate-800/50">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="event_sections[{{ $section->id }}][section_id]" value="{{ $section->id }}">
                            <input type="checkbox" name="event_sections[{{ $section->id }}][use]" value="1" {{ old("event_sections.{$section->id}.use", $isUsed) ? 'checked' : '' }} class="rounded border-slate-300 text-violet-600">
                            <span class="font-medium text-slate-800 dark:text-white">{{ $section->name }}</span>
                        </label>
                        <span class="text-sm text-slate-500 dark:text-slate-400">
                            @if($section->has_seats)
                                Con butacas (filas {{ $section->row_start ?? '?' }}-{{ $section->row_end ?? '?' }})
                            @else
                                Sin butacas @if($section->capacity) — Capacidad {{ $section->capacity }} @endif
                            @endif
                        </span>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-slate-600 dark:text-slate-400">Precio (opcional)</label>
                            <input type="number" name="event_sections[{{ $section->id }}][price]" value="{{ old("event_sections.{$section->id}.price", $currentPrice) }}" min="0" step="0.01" placeholder="—"
                                   class="w-24 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-1.5 text-sm">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div>
            <label for="payment_code_prefix" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Prefijo código de pago (opcional)</label>
            <input id="payment_code_prefix" type="text" name="payment_code_prefix" value="{{ old('payment_code_prefix', $event->payment_code_prefix) }}" maxlength="50"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
        </div>

        <div>
            <label for="cover_image" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Imagen de portada / arte (opcional, reemplaza la actual)</label>
            @if($event->cover_image_path)
                <p class="text-sm text-slate-500 mb-1">Actual: <img src="{{ asset('storage/'.$event->cover_image_path) }}" alt="Portada" class="inline-block h-20 rounded object-cover mt-1"></p>
            @endif
            <input id="cover_image" type="file" name="cover_image" accept="image/*"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('cover_image')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="qr_image" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Imagen QR (opcional, reemplaza la actual)</label>
            @if($event->qr_image_path)
                <p class="text-sm text-slate-500 mb-1">Actual: <img src="{{ asset('storage/'.$event->qr_image_path) }}" alt="QR" class="inline h-12 w-12 object-contain"></p>
            @endif
            <input id="qr_image" type="file" name="qr_image" accept="image/*"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('qr_image')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center">
            <input id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', $event->is_active) ? 'checked' : '' }}
                   class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
            <label for="is_active" class="ml-2 text-sm text-slate-700 dark:text-slate-300">Evento activo</label>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="rounded-lg bg-gradient-to-r from-violet-600 to-fuchsia-600 px-6 py-2 text-white font-medium">Guardar</button>
            <a href="{{ route('admin.events.index') }}" class="rounded-lg border border-slate-300 dark:border-slate-600 px-6 py-2 text-slate-700 dark:text-slate-300">Cancelar</a>
        </div>
    </form>
</div>

<div class="max-w-2xl mt-6">
    <div class="rounded-3xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-xl">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-2">Estado de venta</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Gestiona el estado de ventas de este evento desde aquí.</p>
        @if($event->is_active)
            <form method="POST" action="{{ route('admin.events.sold-out', $event) }}" onsubmit="return confirm('¿Marcar este evento como SOLD OUT? Se bloquearán nuevas reservas.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                    SOLD OUT
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.events.reopen-sales', $event) }}" onsubmit="return confirm('¿Reabrir ventas para este evento?');">
                @csrf
                @method('PATCH')
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-900/30 hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition">
                    Reabrir ventas
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
