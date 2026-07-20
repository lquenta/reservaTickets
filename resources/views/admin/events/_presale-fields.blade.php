@php
    $presaleEnabled = old('presale_enabled', isset($event) ? $event->presale_enabled : false);
    $presaleType = old('presale_discount_type', isset($event) ? $event->presale_discount_type : 'percent');
    $presaleValue = old('presale_discount_value', isset($event) ? $event->presale_discount_value : null);
    $presaleStarts = old(
        'presale_starts_at',
        isset($event) && $event->presale_starts_at ? $event->presale_starts_at->format('Y-m-d\TH:i') : null
    );
    $presaleEnds = old(
        'presale_ends_at',
        isset($event) && $event->presale_ends_at ? $event->presale_ends_at->format('Y-m-d\TH:i') : null
    );
    $hasVenueSections = isset($selectedVenue) && $selectedVenue && $selectedVenue->sections->isNotEmpty();
@endphp
<div class="rounded-xl border-2 border-cyan-200/60 dark:border-cyan-800/50 p-6 space-y-4"
     x-data="{ enabled: {{ $presaleEnabled ? 'true' : 'false' }} }">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Preventa</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                Activa la ventana de fechas. Con sectores, el % o monto se configura
                <strong>por sector</strong> arriba. Sin sectores, usa el descuento único de abajo.
            </p>
        </div>
        <label class="inline-flex items-center gap-2 shrink-0">
            <input type="hidden" name="presale_enabled" value="0">
            <input id="presale_enabled" type="checkbox" name="presale_enabled" value="1"
                   x-model="enabled"
                   {{ $presaleEnabled ? 'checked' : '' }}
                   class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Activar</span>
        </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-2" x-show="enabled" x-cloak>
        <div>
            <label for="presale_starts_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Desde</label>
            <input id="presale_starts_at" type="datetime-local" name="presale_starts_at" value="{{ $presaleStarts }}"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('presale_starts_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="presale_ends_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Hasta</label>
            <input id="presale_ends_at" type="datetime-local" name="presale_ends_at" value="{{ $presaleEnds }}"
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('presale_ends_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        @unless($hasVenueSections)
            <div class="sm:col-span-2 rounded-lg border border-dashed border-cyan-300/60 dark:border-cyan-700/50 p-4 space-y-4">
                <p class="text-sm text-slate-600 dark:text-slate-400">Descuento único (evento sin sectores / precio de plantilla)</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="presale_discount_type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo de descuento</label>
                        <select id="presale_discount_type" name="presale_discount_type"
                                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
                            <option value="percent" {{ $presaleType === 'percent' ? 'selected' : '' }}>Porcentaje (%)</option>
                            <option value="fixed" {{ $presaleType === 'fixed' ? 'selected' : '' }}>Monto fijo (Bs)</option>
                        </select>
                        @error('presale_discount_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="presale_discount_value" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Valor</label>
                        <input id="presale_discount_value" type="number" name="presale_discount_value" value="{{ $presaleValue }}"
                               min="0" step="0.01" placeholder="Ej. 20"
                               class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
                        @error('presale_discount_value')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        @else
            {{-- Conservar valores globales al editar con sectores (no se usan en pricing por sector) --}}
            <input type="hidden" name="presale_discount_type" value="{{ $presaleType }}">
            <input type="hidden" name="presale_discount_value" value="{{ $presaleValue }}">
            <p class="sm:col-span-2 text-sm text-cyan-800 dark:text-cyan-200 bg-cyan-50/80 dark:bg-cyan-950/40 rounded-lg px-3 py-2">
                Con sectores activos, configura el descuento en cada fila de sección (arriba).
            </p>
        @endunless
    </div>
</div>
