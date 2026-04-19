@extends('layouts.admin')

@section('title', 'Editar lugar - Admin')

@section('admin')
<div class="space-y-0">
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

        <div class="rounded-xl border-2 border-violet-200/60 dark:border-violet-700/50 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Secciones (opcional)</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Sectores con butacas: define <strong>rango de filas</strong> y opcionalmente <strong>rango de números de butaca</strong> (columnas). Vacío en columnas = todas las butacas de esas filas. Las zonas con butacas no pueden solaparse. Secciones sin butacas: capacidad fija (entrada general). En cada evento activas secciones y precio.</p>
            <div id="sections-container" class="space-y-4">
                @foreach($venue->sections as $index => $section)
                    <div class="section-row flex flex-wrap gap-4 items-end rounded-lg border border-slate-200 dark:border-slate-600 p-4 bg-slate-50/50 dark:bg-slate-800/50">
                        <input type="hidden" name="sections[{{ $index }}][id]" value="{{ $section->id }}">
                        <div class="flex-1 min-w-[140px]">
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nombre</label>
                            <input type="text" name="sections[{{ $index }}][name]" value="{{ old("sections.{$index}.name", $section->name) }}" maxlength="255" placeholder="Ej. Platea, Palco"
                                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm">
                        </div>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" name="sections[{{ $index }}][has_seats]" value="1" {{ old("sections.{$index}.has_seats", $section->has_seats) ? 'checked' : '' }} class="section-has-seats rounded border-slate-300 text-violet-600">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Con butacas</span>
                        </label>
                        <div class="section-capacity flex-1 min-w-[80px]" style="{{ $section->has_seats ? 'display:none' : '' }}">
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Capacidad</label>
                            <input type="number" name="sections[{{ $index }}][capacity]" value="{{ old("sections.{$index}.capacity", $section->capacity) }}" min="0" placeholder="0"
                                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm">
                        </div>
                        <div class="section-rows flex flex-wrap gap-2 items-end" style="{{ !$section->has_seats ? 'display:none' : '' }}">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Fila desde</label>
                                <input type="number" name="sections[{{ $index }}][row_start]" value="{{ old("sections.{$index}.row_start", $section->row_start) }}" min="1" max="{{ $venue->seat_rows }}" placeholder="1"
                                       class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Fila hasta</label>
                                <input type="number" name="sections[{{ $index }}][row_end]" value="{{ old("sections.{$index}.row_end", $section->row_end) }}" min="1" max="{{ $venue->seat_rows }}" placeholder="{{ $venue->seat_rows }}"
                                       class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Butaca desde <span class="text-slate-400 font-normal">(opc.)</span></label>
                                <input type="number" name="sections[{{ $index }}][col_start]" value="{{ old("sections.{$index}.col_start", $section->col_start) }}" min="1" max="{{ $venue->seat_columns }}" placeholder="Todas"
                                       class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Butaca hasta <span class="text-slate-400 font-normal">(opc.)</span></label>
                                <input type="number" name="sections[{{ $index }}][col_end]" value="{{ old("sections.{$index}.col_end", $section->col_end) }}" min="1" max="{{ $venue->seat_columns }}" placeholder="Todas"
                                       class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm">
                            </div>
                        </div>
                        <button type="button" class="remove-section rounded-lg px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">Quitar</button>
                    </div>
                @endforeach
            </div>
            <button type="button" id="add-section" class="rounded-lg border border-dashed border-slate-300 dark:border-slate-600 px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                + Añadir sección
            </button>
        </div>

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

    <script>
        (function() {
            var container = document.getElementById('sections-container');
            var addBtn = document.getElementById('add-section');
            var sectionIndex = {{ $venue->sections->count() }};
            var maxRow = {{ $venue->seat_rows }};
            var maxCol = {{ $venue->seat_columns }};

            function toggleSectionRow(row) {
                var hasSeats = row.querySelector('.section-has-seats').checked;
                row.querySelector('.section-capacity').style.display = hasSeats ? 'none' : '';
                row.querySelector('.section-rows').style.display = hasSeats ? '' : 'none';
            }

            container.querySelectorAll('.section-row').forEach(function(row) {
                row.querySelector('.section-has-seats').addEventListener('change', function() { toggleSectionRow(row); });
            });

            addBtn.addEventListener('click', function() {
                var html = '<div class="section-row flex flex-wrap gap-4 items-end rounded-lg border border-slate-200 dark:border-slate-600 p-4 bg-slate-50/50 dark:bg-slate-800/50">' +
                    '<input type="hidden" name="sections[' + sectionIndex + '][id]" value="">' +
                    '<div class="flex-1 min-w-[140px]"><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nombre</label>' +
                    '<input type="text" name="sections[' + sectionIndex + '][name]" maxlength="255" placeholder="Ej. Platea, Palco" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm"></div>' +
                    '<label class="inline-flex items-center gap-2"><input type="checkbox" name="sections[' + sectionIndex + '][has_seats]" value="1" checked class="section-has-seats rounded border-slate-300 text-violet-600">' +
                    '<span class="text-sm text-slate-700 dark:text-slate-300">Con butacas</span></label>' +
                    '<div class="section-capacity flex-1 min-w-[80px]" style="display:none"><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Capacidad</label>' +
                    '<input type="number" name="sections[' + sectionIndex + '][capacity]" min="0" placeholder="0" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm"></div>' +
                    '<div class="section-rows flex flex-wrap gap-2 items-end"><div><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Fila desde</label>' +
                    '<input type="number" name="sections[' + sectionIndex + '][row_start]" min="1" max="' + maxRow + '" placeholder="1" class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm"></div>' +
                    '<div><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Fila hasta</label>' +
                    '<input type="number" name="sections[' + sectionIndex + '][row_end]" min="1" max="' + maxRow + '" placeholder="' + maxRow + '" class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm"></div>' +
                    '<div><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Butaca desde (opc.)</label>' +
                    '<input type="number" name="sections[' + sectionIndex + '][col_start]" min="1" max="' + maxCol + '" placeholder="Todas" class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm"></div>' +
                    '<div><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Butaca hasta (opc.)</label>' +
                    '<input type="number" name="sections[' + sectionIndex + '][col_end]" min="1" max="' + maxCol + '" placeholder="Todas" class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm"></div></div>' +
                    '<button type="button" class="remove-section rounded-lg px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">Quitar</button></div>';
                container.insertAdjacentHTML('beforeend', html);
                var newRow = container.lastElementChild;
                newRow.querySelector('.section-has-seats').addEventListener('change', function() { toggleSectionRow(newRow); });
                newRow.querySelector('.remove-section').addEventListener('click', function() { newRow.remove(); });
                sectionIndex++;
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-section')) e.target.closest('.section-row').remove();
            });
        })();
    </script>
    </div>

    @php
        $layoutElementsJson = json_encode($venue->layoutElements->map(function ($e) {
            return [
                'id' => $e->id,
                'type' => $e->type,
                'seat_id' => $e->seat_id,
                'x' => (float) $e->x,
                'y' => (float) $e->y,
                'w' => (float) $e->w,
                'h' => (float) $e->h,
                'rotation' => (float) $e->rotation,
                'z_index' => (int) $e->z_index,
                'meta' => $e->meta ?? [],
                'seat' => $e->seat ? [
                    'id' => $e->seat->id,
                    'label' => $e->seat->display_label,
                    'section_id' => $e->seat->section_id,
                ] : null,
            ];
        })->values()->all(), JSON_THROW_ON_ERROR);
        $venueSeatsJson = json_encode($venue->seats->map(fn ($s) => [
            'id' => $s->id,
            'label' => $s->display_label,
            'row' => $s->row,
            'row_letter' => $s->row_letter,
            'number' => $s->number,
            'section_id' => $s->section_id,
        ])->values()->all(), JSON_THROW_ON_ERROR);
        $venueSectionsJson = json_encode($venue->sections->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
        ])->values()->all(), JSON_THROW_ON_ERROR);
    @endphp
    <div id="layout-editor-root" class="mt-10 w-full max-w-7xl xl:max-w-[min(96rem,calc(100vw-13rem))] rounded-3xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-5 sm:p-6 shadow-xl">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between sm:gap-4 mb-5 border-b border-slate-200/80 dark:border-slate-600/80 pb-4">
            <div class="min-w-0 flex-1">
                <h2 class="text-lg sm:text-xl font-semibold text-slate-800 dark:text-white">Plano del lugar (editor gráfico)</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Arrastra elementos, redimensiona con las esquinas y rota. En <strong class="text-slate-700 dark:text-slate-200">Inserción por filas</strong> indica la fila como letra (<span class="font-mono">A</span>, <span class="font-mono">B</span>…) o como número interno (<span class="font-mono">1</span>, <span class="font-mono">2</span>…), igual que en el grid del venue.</p>
                <p id="layout-seat-stats" class="mt-2 inline-flex flex-wrap items-center gap-2 text-xs text-slate-600 dark:text-slate-400"></p>
            </div>
            <button id="layout-save-btn" type="button" class="shrink-0 rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-5 py-2.5 text-white text-sm font-semibold shadow-md hover:from-violet-500 hover:to-fuchsia-500 focus:outline-none focus:ring-2 focus:ring-violet-400 focus:ring-offset-2 dark:focus:ring-offset-slate-800">
                Guardar layout
            </button>
        </div>

        <div class="flex flex-col gap-4">
            <div id="layout-ribbon-root" class="flex flex-col overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-900/50 shadow-sm">
                <div class="flex shrink-0 items-center justify-between gap-2 border-b border-slate-200/80 dark:border-slate-600/80 bg-slate-100/90 dark:bg-slate-800/70 px-2 py-1 sm:px-3">
                    <span class="truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">Herramientas del plano</span>
                    <button type="button" id="layout-ribbon-toggle" class="inline-flex shrink-0 items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500" aria-expanded="true" aria-controls="layout-ribbon-body">
                        <span id="layout-ribbon-toggle-text">Ocultar cinta</span>
                        <svg id="layout-ribbon-toggle-icon" class="h-4 w-4 shrink-0 text-slate-600 transition-transform dark:text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </button>
                </div>
                <div id="layout-ribbon-body" class="overflow-x-auto">
                <nav class="flex flex-nowrap items-start gap-0 divide-x divide-slate-200 dark:divide-slate-600 px-1 py-2 sm:px-2 min-w-min" aria-label="Cinta del editor de layout">
                    <div class="flex shrink-0 flex-col px-2 sm:px-3 w-40 min-w-0">
                    <details class="layout-ribbon-section group min-w-0 open" data-ribbon-section="library">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-1 rounded-md py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden">
                            <span>Biblioteca</span>
                            <span class="shrink-0 text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true">▼</span>
                        </summary>
                        <div class="mt-1 grid grid-cols-2 gap-2 pb-1">
                            <button type="button" data-add-type="stage" class="layout-add rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-left text-sm font-medium text-slate-800 dark:text-slate-100 shadow-sm hover:bg-violet-50 dark:hover:bg-violet-950/30 hover:border-violet-300 dark:hover:border-violet-600 transition">Escenario</button>
                            <button type="button" data-add-type="speaker" class="layout-add rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2.5 text-left text-sm font-medium text-slate-800 dark:text-slate-100 shadow-sm hover:bg-violet-50 dark:hover:bg-violet-950/30 hover:border-violet-300 dark:hover:border-violet-600 transition">Parlante</button>
                        </div>
                    </details>
                    </div>

                    <div class="flex shrink-0 flex-col px-2 sm:px-3 w-52 min-w-0">
                    <details class="layout-ribbon-section group min-w-0 open" data-ribbon-section="seat">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-1 rounded-md py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden">
                            <span>Añadir butaca</span>
                            <span class="shrink-0 text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true">▼</span>
                        </summary>
                        <div class="mt-1 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800/60 p-3 shadow-sm">
                            <label for="layout-seat-select" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">Añadir una butaca</label>
                            <select id="layout-seat-select" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2.5 text-sm">
                                <option value="">Elegir en la lista…</option>
                            </select>
                            <button id="layout-add-seat-btn" type="button" class="mt-2 w-full rounded-lg bg-slate-800 dark:bg-slate-200 text-white dark:text-slate-900 px-3 py-2 text-sm font-medium hover:bg-slate-700 dark:hover:bg-white transition">Colocar butaca seleccionada</button>
                        </div>
                    </details>
                    </div>

                    <div class="flex shrink-0 flex-col px-2 sm:px-3 w-[min(22rem,calc(100vw-8rem))] min-w-0 max-h-[min(24rem,55vh)] overflow-y-auto">
                    <details class="layout-ribbon-section group min-w-0 open" data-ribbon-section="bulk">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-1 rounded-md py-1.5 pr-0.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden">
                            <span class="min-w-0 truncate">Inserción masiva</span>
                            <span class="shrink-0 text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true">▼</span>
                        </summary>
                        <div class="mt-1 rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50/50 dark:bg-violet-950/20 p-3 shadow-sm space-y-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Inserción por filas</h3>
                        <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">Rellena una banda de butacas libres de una fila (orden por número de asiento).</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="col-span-2">
                            <label for="bulk-row-letter" class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-0.5">Fila (letra A–Z o número 1…)</label>
                            <input id="bulk-row-letter" type="text" maxlength="3" value="A" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-2 text-sm uppercase" placeholder="A o 1">
                        </div>
                        <div>
                            <label for="bulk-per-row" class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-0.5">Butacas por fila</label>
                            <input id="bulk-per-row" type="number" min="1" max="50" value="10" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-2 text-sm">
                        </div>
                        <div>
                            <label for="bulk-rows" class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-0.5">Cantidad de filas</label>
                            <input id="bulk-rows" type="number" min="1" max="50" value="1" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <span class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">Atajo: butacas por fila</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ([5, 8, 10, 12, 16] as $n)
                                <button type="button" class="bulk-preset-per-row rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-200 hover:border-violet-500 hover:text-violet-700 dark:hover:border-violet-400 dark:hover:text-violet-300" data-per-row="{{ $n }}">{{ $n }}</button>
                            @endforeach
                        </div>
                    </div>
                    <details class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white/70 dark:bg-slate-800/50 px-2 py-1 group">
                        <summary class="cursor-pointer list-none py-2 px-1 text-xs font-medium text-slate-700 dark:text-slate-300 marker:content-none flex items-center justify-between">
                            <span>Tamaño, separación y posición inicial</span>
                            <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                        </summary>
                        <div class="grid grid-cols-2 gap-2 pb-3 pt-1 border-t border-slate-200/80 dark:border-slate-600/80 mt-1">
                            <div>
                                <label for="bulk-seat-w" class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-0.5">Ancho butaca</label>
                                <input id="bulk-seat-w" type="number" min="16" max="200" value="52" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-sm">
                            </div>
                            <div>
                                <label for="bulk-seat-h" class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-0.5">Alto butaca</label>
                                <input id="bulk-seat-h" type="number" min="16" max="200" value="52" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-sm">
                            </div>
                            <div>
                                <label for="bulk-gap-x" class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-0.5">Hueco horizontal</label>
                                <input id="bulk-gap-x" type="number" min="0" max="80" value="6" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-sm">
                            </div>
                            <div>
                                <label for="bulk-gap-y" class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-0.5">Hueco vertical</label>
                                <input id="bulk-gap-y" type="number" min="0" max="80" value="6" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-sm">
                            </div>
                            <div class="col-span-2">
                                <label for="bulk-start-x" class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-0.5">Origen (px) X · Y</label>
                                <div class="flex gap-2">
                                    <input id="bulk-start-x" type="number" min="0" value="48" class="w-1/2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-sm">
                                    <input id="bulk-start-y" type="number" min="0" value="120" class="w-1/2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-1.5 text-sm">
                                </div>
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                    <button id="layout-pick-origin-btn" type="button" class="rounded-lg border border-violet-400/60 dark:border-violet-500/60 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-semibold text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-950/30">
                                        Elegir origen con clic
                                    </button>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Tip: al activar, haz clic en el fondo del lienzo para setear X/Y.</p>
                                </div>
                            </div>
                        </div>
                    </details>
                    <button id="layout-bulk-rows-btn" type="button" class="w-full rounded-xl bg-violet-600 hover:bg-violet-500 px-3 py-2.5 text-sm font-semibold text-white shadow-sm">
                        Insertar filas en el plano
                    </button>

                    <div class="border-t border-violet-200/80 dark:border-violet-900/50 pt-3 mt-3 space-y-2">
                        <h4 class="text-xs font-semibold text-slate-800 dark:text-slate-100">Matriz (varias filas × números)</h4>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400">Ubica en cuadrícula las butacas que existan en el venue (misma fila y número). Si falta una combinación o ya está en el plano, queda un hueco en esa celda.</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="matrix-row-start" class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-0.5">Fila inicial</label>
                                <input id="matrix-row-start" type="text" maxlength="3" value="A" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-2 text-sm uppercase" placeholder="A">
                            </div>
                            <div>
                                <label for="matrix-row-end" class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-0.5">Fila final</label>
                                <input id="matrix-row-end" type="text" maxlength="3" value="B" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-2 text-sm uppercase" placeholder="B">
                            </div>
                            <div>
                                <label for="matrix-col-start" class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-0.5">Butaca desde</label>
                                <input id="matrix-col-start" type="number" min="1" max="99" value="1" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-2 text-sm">
                            </div>
                            <div>
                                <label for="matrix-col-end" class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-0.5">Butaca hasta</label>
                                <input id="matrix-col-end" type="number" min="1" max="99" value="10" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-2 py-2 text-sm">
                            </div>
                        </div>
                        <button id="layout-bulk-matrix-btn" type="button" class="w-full rounded-xl border-2 border-violet-500 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm font-semibold text-violet-700 dark:text-violet-300 shadow-sm hover:bg-violet-50 dark:hover:bg-violet-950/40">
                            Insertar matriz en el plano
                        </button>
                    </div>
                </div>
                    </details>
                    </div>

                    <div class="flex shrink-0 flex-col px-2 sm:px-3 w-52 min-w-0">
                    <details class="layout-ribbon-section group min-w-0 open" data-ribbon-section="layers">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-1 rounded-md py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden">
                            <span class="min-w-0 truncate">Capas y rotación</span>
                            <span class="shrink-0 text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true">▼</span>
                        </summary>
                        <div class="mt-1 rounded-xl border border-slate-200 dark:border-slate-700 p-3 bg-white/60 dark:bg-slate-800/40 space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <button id="layout-btn-front" type="button" class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-2 py-2 text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-700">Al frente</button>
                        <button id="layout-btn-back" type="button" class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-2 py-2 text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-700">Atrás</button>
                        <button id="layout-btn-dup" type="button" class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-2 py-2 text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-700">Duplicar</button>
                        <button id="layout-btn-reset" type="button" class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-2 py-2 text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-700">Quitar rotación</button>
                        <button id="layout-btn-delete" type="button" class="rounded-lg border border-red-300/60 bg-red-50/40 dark:bg-red-900/20 px-2 py-2 text-xs font-semibold text-red-700 dark:text-red-200 hover:bg-red-50/70 dark:hover:bg-red-900/30">
                            Eliminar
                        </button>
                    </div>
                    <p class="text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">Teclado: <kbd class="rounded border border-slate-300 dark:border-slate-600 px-1 font-mono text-[10px]">Ctrl</kbd>+<kbd class="rounded border px-1 font-mono text-[10px]">C</kbd>/<kbd class="rounded border px-1 font-mono text-[10px]">V</kbd>, <kbd class="rounded border px-1 font-mono text-[10px]">Ctrl</kbd>+<kbd class="rounded border px-1 font-mono text-[10px]">D</kbd>, <kbd class="rounded border px-1 font-mono text-[10px]">Esc</kbd>, <kbd class="rounded border px-1 font-mono text-[10px]">Supr</kbd> (doble clic en un elemento también lo borra).</p>
                        </div>
                    </details>
                    </div>

                    <div class="flex shrink-0 flex-col px-2 sm:px-3 w-48 min-w-0">
                    <details class="layout-ribbon-section group min-w-0 open" data-ribbon-section="snap">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-1 rounded-md py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden">
                            <span>Imán (snap)</span>
                            <span class="shrink-0 text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true">▼</span>
                        </summary>
                        <div class="mt-1 rounded-xl border border-slate-200 dark:border-slate-700 p-3 bg-white/60 dark:bg-slate-800/40 space-y-2">
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input id="layout-snap-grid" type="checkbox" class="rounded border-slate-300 text-violet-600" checked disabled>
                        Alinear a cuadrícula 16px
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input id="layout-snap-rotate" type="checkbox" class="rounded border-slate-300 text-violet-600" checked disabled>
                        Rotar de a 15°
                    </label>
                        </div>
                    </details>
                    </div>

                    <div class="flex shrink-0 flex-col px-2 sm:px-3 w-[min(18rem,calc(100vw-10rem))] min-w-0">
                    <details class="layout-ribbon-section group min-w-0 open" data-ribbon-section="sections">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-1 rounded-md py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 marker:content-none [&::-webkit-details-marker]:hidden">
                            <span>Secciones</span>
                            <span class="shrink-0 text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true">▼</span>
                        </summary>
                        <div class="mt-1 rounded-xl border border-slate-200 dark:border-slate-700 p-3 bg-white/60 dark:bg-slate-800/40 space-y-2">
                    <div>
                        <label for="layout-seat-section-select" class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-1">
                            Seccion de butaca seleccionada
                        </label>
                        <div class="flex gap-2">
                            <select id="layout-seat-section-select" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm">
                                <option value="">Sin seccion</option>
                                @foreach($venue->sections as $section)
                                    <option value="{{ $section->id }}">{{ $section->name }}</option>
                                @endforeach
                            </select>
                            <button id="layout-seat-section-apply" type="button" class="rounded-lg border border-violet-300 dark:border-violet-700 px-3 py-2 text-xs font-semibold text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-900/20">
                                Aplicar
                            </button>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Selecciona una butaca en el lienzo y asigna su seccion.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input id="layout-color-by-section" type="checkbox" class="rounded border-slate-300 text-violet-600">
                        Un solo color para todas las butacas (ignora secciones)
                    </label>
                    <div>
                        <label for="layout-section-view" class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-1">
                            Ver sección
                        </label>
                        <select id="layout-section-view" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm">
                            <option value="0">Todas</option>
                            @foreach($venue->sections as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Ayuda a verificar que el layout coincide con los rangos de secciones.</p>
                        </div>
                    </details>
                    </div>
                </nav>
                </div>
            </div>

            <div class="min-w-0 max-w-full flex flex-col gap-2">
                <div id="layout-canvas-shell" class="relative rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-950 shadow-inner overflow-auto touch-none" style="min-height: min(92vh, 1400px);">
                    <div class="absolute right-2 top-2 z-30 flex flex-wrap items-center justify-end gap-1 rounded-lg border border-white/10 bg-slate-900/95 px-1.5 py-1 shadow-lg backdrop-blur-sm">
                        <button id="layout-pan-toggle" type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-400" title="Mano: arrastra el lienzo (vuelve a pulsar para editar elementos)" aria-label="Modo mano para desplazar el lienzo">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 11V6a2 2 0 0 0-2-2 2 2 0 0 0-2 2" />
                                <path d="M14 10V4a2 2 0 0 0-2-2 2 2 0 0 0-2 2v2" />
                                <path d="M10 10.5V6a2 2 0 0 0-2-2 2 2 0 0 0-2 2v8" />
                                <path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 .713-3.27A2 2 0 0 1 6 18.66V15" />
                            </svg>
                        </button>
                        <span id="layout-zoom-label" class="px-2 text-[11px] font-mono text-slate-300 tabular-nums">100%</span>
                        <button id="layout-zoom-out" type="button" class="rounded-md px-2 py-1 text-sm font-medium text-white hover:bg-white/10" title="Alejar" aria-label="Alejar zoom">−</button>
                        <button id="layout-zoom-reset" type="button" class="rounded-md px-2 py-1 text-xs font-medium text-violet-200 hover:bg-white/10" title="Vista al 100% y centrado por defecto" aria-label="Restablecer zoom">100%</button>
                        <button id="layout-zoom-in" type="button" class="rounded-md px-2 py-1 text-sm font-medium text-white hover:bg-white/10" title="Acercar" aria-label="Acercar zoom">+</button>
                    </div>
                    <div class="absolute left-2 top-2 z-10 flex items-center gap-2 rounded-lg border border-white/10 bg-slate-900/85 px-2 py-1 shadow-lg backdrop-blur-sm">
                        <span class="text-[10px] uppercase tracking-wide text-white/50">Cursor</span>
                        <span id="layout-cursor-coords" class="font-mono text-[11px] text-slate-200 tabular-nums">x:— y:—</span>
                        <span id="layout-origin-pick-badge" class="hidden rounded-md bg-violet-600/90 px-1.5 py-0.5 text-[10px] font-semibold text-white">Elegir origen…</span>
                    </div>
                    <p class="pointer-events-none absolute left-2 bottom-2 z-20 max-w-[min(92%,32rem)] rounded-md bg-black/35 px-2 py-1 text-[10px] leading-snug text-slate-200 sm:text-xs">Icono de mano arriba a la derecha: desplazar el lienzo. Rueda del ratón para zoom. También <kbd class="rounded bg-white/10 px-0.5">Ctrl</kbd>+rueda o +/−.</p>
                    <div id="layout-konva-host" class="h-[min(92vh,1400px)] min-h-[760px] w-full max-w-none min-w-[1600px]"></div>
                </div>
                <p id="layout-status" class="min-h-[1.25rem] text-sm text-slate-600 dark:text-slate-400"></p>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/konva@9.3.0/konva.min.js" crossorigin="anonymous"></script>
    <script>
        (function() {
            const elements = {!! $layoutElementsJson !!};
            const seats = {!! $venueSeatsJson !!};
            const sections = {!! $venueSectionsJson !!};
            const host = document.getElementById('layout-konva-host');
            const statusEl = document.getElementById('layout-status');
            const saveBtn = document.getElementById('layout-save-btn');
            const seatSelect = document.getElementById('layout-seat-select');
            const addSeatBtn = document.getElementById('layout-add-seat-btn');
            const snapGridEl = document.getElementById('layout-snap-grid');
            const snapRotateEl = document.getElementById('layout-snap-rotate');
            const colorBySectionEl = document.getElementById('layout-color-by-section');
            const sectionViewSelectEl = document.getElementById('layout-section-view');
            const seatSectionSelectEl = document.getElementById('layout-seat-section-select');
            const seatSectionApplyBtn = document.getElementById('layout-seat-section-apply');
            const btnFront = document.getElementById('layout-btn-front');
            const btnBack = document.getElementById('layout-btn-back');
            const btnDup = document.getElementById('layout-btn-dup');
            const btnReset = document.getElementById('layout-btn-reset');
            const btnDelete = document.getElementById('layout-btn-delete');
            const bulkRowsBtn = document.getElementById('layout-bulk-rows-btn');
            const bulkRowLetter = document.getElementById('bulk-row-letter');
            const bulkPerRow = document.getElementById('bulk-per-row');
            const bulkRows = document.getElementById('bulk-rows');
            const bulkSeatW = document.getElementById('bulk-seat-w');
            const bulkSeatH = document.getElementById('bulk-seat-h');
            const bulkGapX = document.getElementById('bulk-gap-x');
            const bulkGapY = document.getElementById('bulk-gap-y');
            const bulkStartX = document.getElementById('bulk-start-x');
            const bulkStartY = document.getElementById('bulk-start-y');
            const pickOriginBtn = document.getElementById('layout-pick-origin-btn');
            const cursorCoordsEl = document.getElementById('layout-cursor-coords');
            const originPickBadgeEl = document.getElementById('layout-origin-pick-badge');
            const matrixRowStartEl = document.getElementById('matrix-row-start');
            const matrixRowEndEl = document.getElementById('matrix-row-end');
            const matrixColStartEl = document.getElementById('matrix-col-start');
            const matrixColEndEl = document.getElementById('matrix-col-end');
            const bulkMatrixBtn = document.getElementById('layout-bulk-matrix-btn');
            const venueSeatRows = {{ (int) $venue->seat_rows }};
            const venueSeatColumns = {{ (int) $venue->seat_columns }};
            const zoomLabelEl = document.getElementById('layout-zoom-label');
            const zoomInBtn = document.getElementById('layout-zoom-in');
            const zoomOutBtn = document.getElementById('layout-zoom-out');
            const zoomResetBtn = document.getElementById('layout-zoom-reset');
            const panToggleBtn = document.getElementById('layout-pan-toggle');
            const seatStatsEl = document.getElementById('layout-seat-stats');
            const csrf = '{{ csrf_token() }}';
            const saveUrl = '{{ route('admin.venues.layout.save', $venue) }}';

            let dirty = false;
            let localId = -1;
            let stage = null;
            let layer = null;
            let transformer = null;
            let gridLayer = null;
            let selectedGroup = null;
            let selectedGroupIds = [];
            let selectionRect = null;
            let selectionRectStart = null;
            let isSelecting = false;
            let clipboard = null;
            let resizeObserver = null;
            let lastHostSizeKey = null;
            let pickOriginMode = false;
            let panMode = false;

            const GRID = 16;
            const ROT_SNAP = 15;
            const MIN_W = 20;
            const MIN_H = 20;
            const ZOOM_MIN = 0.5;
            const ZOOM_MAX = 2;
            const CANVAS_H_MIN = 760;
            const CANVAS_H_MAX = 1400;

            function setStatus(msg, kind) {
                statusEl.textContent = msg || '';
                statusEl.classList.remove('text-red-500', 'text-amber-500', 'text-emerald-600', 'text-slate-500', 'dark:text-slate-400');
                if (kind === 'error') statusEl.classList.add('text-red-500');
                else if (kind === 'ok') statusEl.classList.add('text-emerald-600');
                else if (kind === 'warn') statusEl.classList.add('text-amber-500');
                else statusEl.classList.add('text-slate-500', 'dark:text-slate-400');
            }

            function konvaFindToArray(found) {
                if (!found) return [];
                if (Array.isArray(found)) return found;
                if (typeof found.toArray === 'function') return found.toArray();
                if (typeof found.length === 'number') return Array.from(found);
                return [];
            }

            function normalizeSeatSectionId(v) {
                if (v === null || v === undefined || v === '') return null;
                const n = Number(v);
                return Number.isFinite(n) ? n : null;
            }

            function buildSeatSectionsPayload() {
                const acc = {};
                seats.forEach(function(seat) {
                    acc[String(seat.id)] = normalizeSeatSectionId(seat.section_id);
                });
                elements.forEach(function(el) {
                    if (el.type !== 'seat' || !el.seat_id || !el.seat) return;
                    if (!Object.prototype.hasOwnProperty.call(el.seat, 'section_id')) return;
                    acc[String(el.seat_id)] = normalizeSeatSectionId(el.seat.section_id);
                });
                return acc;
            }

            /** Alinea seats[] con section_id embebido en cada elemento seat del layout (misma fuente que el servidor). */
            function syncSeatsFromLayoutElements() {
                elements.forEach(function(el) {
                    if (el.type !== 'seat' || !el.seat_id || !el.seat) return;
                    if (!Object.prototype.hasOwnProperty.call(el.seat, 'section_id')) return;
                    const seat = seats.find(function(s) { return Number(s.id) === Number(el.seat_id); });
                    if (!seat) return;
                    seat.section_id = normalizeSeatSectionId(el.seat.section_id);
                });
            }

            function resolveSeatSectionIdForLayoutEl(el) {
                if (!el || el.type !== 'seat' || !el.seat_id) return null;
                if (el.seat && Object.prototype.hasOwnProperty.call(el.seat, 'section_id')) {
                    return normalizeSeatSectionId(el.seat.section_id);
                }
                const seat = seats.find(function(s) { return Number(s.id) === Number(el.seat_id); });
                return seat ? normalizeSeatSectionId(seat.section_id) : null;
            }

            function seatColorsForLayoutElement(el) {
                if (el.type !== 'seat') return colorsForType(el.type);
                const sectionId = resolveSeatSectionIdForLayoutEl(el);
                if (!sectionId) return colorsForType('seat');
                return sectionPalette(sectionId);
            }

            function markDirty(msg) {
                dirty = true;
                setStatus(msg || 'Hay cambios sin guardar.', 'warn');
            }

            function snapPos(v) {
                return Math.round(v / GRID) * GRID;
            }

            function snapRot(r) {
                return Math.round(r / ROT_SNAP) * ROT_SNAP;
            }

            function clampGroupToStage(group) {
                const w = stage.width();
                const h = stage.height();
                const gw = group.width();
                const gh = group.height();
                group.x(Math.max(0, Math.min(w - gw, group.x())));
                group.y(Math.max(0, Math.min(h - gh, group.y())));
            }

            function normalizeZ() {
                elements.sort((a, b) => (a.z_index ?? 0) - (b.z_index ?? 0));
                elements.forEach((el, i) => { el.z_index = i + 1; });
            }

            function availableSeats() {
                const used = new Set(elements.filter(e => e.type === 'seat' && e.seat_id).map(e => Number(e.seat_id)));
                return seats.filter(s => !used.has(Number(s.id)));
            }

            function refreshSeatSelect() {
                const options = ['<option value="">Elegir en la lista…</option>'].concat(
                    availableSeats().map(s => '<option value="' + s.id + '">' + s.label + '</option>')
                );
                seatSelect.innerHTML = options.join('');
                refreshSeatStats();
            }

            function seatById(seatId) {
                return seats.find(s => Number(s.id) === Number(seatId)) || null;
            }

            function sectionExists(sectionId) {
                return sections.some(s => Number(s.id) === Number(sectionId));
            }

            function refreshSeatSectionPickerFromSelection() {
                if (!seatSectionSelectEl) return;
                const selectedSeatModels = elements.filter(function(el) {
                    return selectedGroupIds.includes(Number(el.id)) && el.type === 'seat' && !!el.seat_id;
                });
                if (!selectedSeatModels.length) {
                    seatSectionSelectEl.value = '';
                    return;
                }
                const sectionValues = selectedSeatModels.map(function(m) {
                    const seat = seatById(m.seat_id);
                    return seat && seat.section_id != null ? String(seat.section_id) : '';
                });
                const first = sectionValues[0] ?? '';
                const allSame = sectionValues.every(function(v) { return v === first; });
                seatSectionSelectEl.value = allSame ? first : '';
            }

            function refreshSeatStats() {
                if (!seatStatsEl) return;
                const onLayout = elements.filter(e => e.type === 'seat' && e.seat_id).length;
                const free = availableSeats().length;
                const total = seats.length;
                seatStatsEl.innerHTML =
                    '<span class="inline-flex items-center rounded-full bg-slate-200/90 px-2.5 py-0.5 font-medium text-slate-800 dark:bg-slate-700 dark:text-slate-100">' +
                    onLayout + ' en el plano</span>' +
                    '<span class="inline-flex items-center rounded-full bg-emerald-100/90 px-2.5 py-0.5 font-medium text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-200">' +
                    free + ' libres para colocar</span>' +
                    '<span class="text-slate-500 dark:text-slate-500">· ' + total + ' butacas en el venue</span>';
            }

            function normRowStr(v) {
                if (v === null || v === undefined) return '';
                return String(v).trim();
            }

            /**
             * El venue guarda row como entero (1=A, 2=B…); la UI suele usar letra.
             * Acepta filtro "A"/"a" o "1" y lo compara con row_letter y row del asiento.
             */
            function seatMatchesRowFilter(seat, filterRaw) {
                const f = normRowStr(filterRaw).toLowerCase();
                if (!f) return false;
                const letter = normRowStr(seat.row_letter != null ? seat.row_letter : '').toLowerCase();
                if (letter && letter === f) return true;
                const rowNum = seat.row;
                if (rowNum !== null && rowNum !== undefined && String(rowNum) === f) return true;
                if (letter && f.length === 1 && f >= 'a' && f <= 'z') {
                    const idx = f.charCodeAt(0) - 96;
                    if (Number(rowNum) === idx) return true;
                }
                return false;
            }

            function seatNumberForSort(s) {
                const n = Number(s.number);
                return Number.isFinite(n) ? n : 0;
            }

            /** Fila del venue: entero (1,2,…) o letra A–Z (= 1–26). */
            function parseVenueRowIndex(raw) {
                const s = normRowStr(raw).toUpperCase();
                if (!s) return null;
                if (/^\d+$/.test(s)) {
                    const n = parseInt(s, 10);
                    return Number.isFinite(n) ? n : null;
                }
                if (s.length === 1 && s >= 'A' && s <= 'Z') {
                    return s.charCodeAt(0) - 64;
                }
                const n = parseInt(s, 10);
                return Number.isFinite(n) ? n : null;
            }

            function expandVenueRowIndices(startRaw, endRaw) {
                const r1 = parseVenueRowIndex(startRaw);
                const r2 = parseVenueRowIndex(endRaw);
                if (r1 == null || r2 == null) return null;
                let lo = Math.min(r1, r2);
                let hi = Math.max(r1, r2);
                lo = Math.max(1, lo);
                const maxRow = Math.max(venueSeatRows, 1);
                hi = Math.min(hi, maxRow);
                if (lo > hi) {
                    return null;
                }
                const out = [];
                for (let r = lo; r <= hi; r++) {
                    out.push(r);
                }
                return out.length ? out : null;
            }

            function readBulkLayoutParams() {
                return {
                    seatW: Math.max(MIN_W, Math.min(200, parseInt(bulkSeatW && bulkSeatW.value, 10) || 52)),
                    seatH: Math.max(MIN_H, Math.min(200, parseInt(bulkSeatH && bulkSeatH.value, 10) || 52)),
                    gapX: Math.max(0, Math.min(80, parseInt(bulkGapX && bulkGapX.value, 10) || 0)),
                    gapY: Math.max(0, Math.min(80, parseInt(bulkGapY && bulkGapY.value, 10) || 0)),
                    startX: Math.max(0, parseInt(bulkStartX && bulkStartX.value, 10) || 0),
                    startY: Math.max(0, parseInt(bulkStartY && bulkStartY.value, 10) || 0),
                };
            }

            function insertBulkSeatMatrix() {
                if (!stage) {
                    setStatus('El editor aún no está listo. Espera un momento o recarga.', 'error');
                    return;
                }
                const rowStartRaw = matrixRowStartEl ? matrixRowStartEl.value : '';
                const rowEndRaw = matrixRowEndEl ? matrixRowEndEl.value : '';
                if (!normRowStr(rowStartRaw) || !normRowStr(rowEndRaw)) {
                    setStatus('Indica fila inicial y fila final de la matriz (letra o número).', 'error');
                    return;
                }
                const rowIndices = expandVenueRowIndices(rowStartRaw, rowEndRaw);
                if (!rowIndices || rowIndices.length === 0) {
                    setStatus('No se pudo interpretar el rango de filas. Usa letras A–Z o números de fila del venue.', 'error');
                    return;
                }
                if (rowIndices.length > 50) {
                    setStatus('Demasiadas filas a la vez (máx. 50). Reduce el rango.', 'error');
                    return;
                }
                let cLo = parseInt(matrixColStartEl && matrixColStartEl.value, 10) || 1;
                let cHi = parseInt(matrixColEndEl && matrixColEndEl.value, 10) || 1;
                if (cLo > cHi) {
                    const t = cLo;
                    cLo = cHi;
                    cHi = t;
                }
                cLo = Math.max(1, cLo);
                const maxCol = Math.max(venueSeatColumns, 1);
                cHi = Math.min(Math.max(cHi, cLo), maxCol, 99);
                const ncols = cHi - cLo + 1;
                if (ncols > 50) {
                    setStatus('Demasiadas columnas (números de butaca) a la vez (máx. 50).', 'error');
                    return;
                }
                const used = new Set(elements.filter(e => e.type === 'seat' && e.seat_id).map(e => Number(e.seat_id)));
                const { seatW, seatH, gapX, gapY, startX, startY } = readBulkLayoutParams();
                const sw = stage.width();
                const sh = stage.height();
                let maxZ = elements.reduce((m, x) => Math.max(m, x.z_index || 0), 0);
                let placed = 0;
                let missingVenue = 0;
                let skippedUsed = 0;

                rowIndices.forEach(function(venueRow, rVisual) {
                    for (let n = cLo; n <= cHi; n++) {
                        const cVisual = n - cLo;
                        const seat = seats.find(function(s) {
                            return Number(s.row) === venueRow && Number(s.number) === n;
                        });
                        if (!seat) {
                            missingVenue += 1;
                            continue;
                        }
                        if (used.has(Number(seat.id))) {
                            skippedUsed += 1;
                            continue;
                        }
                        used.add(Number(seat.id));
                        maxZ += 1;
                        placed += 1;
                        elements.push({
                            id: localId--,
                            type: 'seat',
                            seat_id: seat.id,
                            x: snapPos(startX + cVisual * (seatW + gapX)),
                            y: snapPos(startY + rVisual * (seatH + gapY)),
                            w: seatW,
                            h: seatH,
                            rotation: 0,
                            z_index: maxZ,
                            meta: {},
                            seat: { id: seat.id, label: seat.label, section_id: seat.section_id != null ? seat.section_id : null },
                        });
                    }
                });

                if (placed === 0) {
                    setStatus('No se colocó ninguna butaca: revisa filas/números o libera las que ya están en el plano.', 'error');
                    return;
                }

                normalizeZ();
                refreshSeatSelect();
                rebuildScene();
                markDirty('Matriz de butacas insertada.');

                const maxX = snapPos(startX + (ncols - 1) * (seatW + gapX)) + seatW;
                const maxY = snapPos(startY + (rowIndices.length - 1) * (seatH + gapY)) + seatH;
                let msg = 'Matriz: ' + placed + ' butaca(s) colocada(s) alineadas por fila y número.';
                if (missingVenue) {
                    msg += ' ' + missingVenue + ' celda(s) sin butaca en el venue.';
                }
                if (skippedUsed) {
                    msg += ' ' + skippedUsed + ' omitida(s) (ya en el plano).';
                }
                if (maxX > sw || maxY > sh) {
                    msg += ' Parte queda fuera del lienzo: reduce tamaño o huecos, o aleja el zoom.';
                }
                const warn = missingVenue || skippedUsed || maxX > sw || maxY > sh;
                setStatus(msg, warn ? 'warn' : 'ok');
            }

            function insertBulkSeatRows() {
                if (!stage) {
                    setStatus('El editor aún no está listo. Espera un momento o recarga.', 'error');
                    return;
                }
                const letter = bulkRowLetter ? bulkRowLetter.value : '';
                if (!normRowStr(letter)) {
                    setStatus('Indica la fila (letra o código) que quieres colocar.', 'error');
                    return;
                }
                const perRow = Math.max(1, Math.min(50, parseInt(bulkPerRow && bulkPerRow.value, 10) || 10));
                const rowCount = Math.max(1, Math.min(50, parseInt(bulkRows && bulkRows.value, 10) || 1));
                const { seatW, seatH, gapX, gapY, startX, startY } = readBulkLayoutParams();

                const used = new Set(elements.filter(e => e.type === 'seat' && e.seat_id).map(e => Number(e.seat_id)));
                const pool = seats
                    .filter(s => !used.has(Number(s.id)) && seatMatchesRowFilter(s, letter))
                    .sort((a, b) => seatNumberForSort(a) - seatNumberForSort(b));

                const needed = perRow * rowCount;
                if (pool.length === 0) {
                    setStatus('No hay butacas libres de esa fila en el venue (o ya están en el plano).', 'error');
                    return;
                }
                const picked = pool.slice(0, needed);
                let maxZ = elements.reduce((m, x) => Math.max(m, x.z_index || 0), 0);
                const sw = stage.width();
                const sh = stage.height();

                picked.forEach(function(seat, i) {
                    const r = Math.floor(i / perRow);
                    const c = i % perRow;
                    const x = snapPos(startX + c * (seatW + gapX));
                    const y = snapPos(startY + r * (seatH + gapY));
                    maxZ += 1;
                    elements.push({
                        id: localId--,
                        type: 'seat',
                        seat_id: seat.id,
                        x: x,
                        y: y,
                        w: seatW,
                        h: seatH,
                        rotation: 0,
                        z_index: maxZ,
                        meta: {},
                        seat: { id: seat.id, label: seat.label, section_id: seat.section_id != null ? seat.section_id : null },
                    });
                });

                normalizeZ();
                refreshSeatSelect();
                rebuildScene();
                markDirty('Filas de butacas insertadas.');

                const lastR = Math.floor((picked.length - 1) / perRow);
                const lastC = (picked.length - 1) % perRow;
                const maxX = snapPos(startX + lastC * (seatW + gapX)) + seatW;
                const maxY = snapPos(startY + lastR * (seatH + gapY)) + seatH;
                let msg = 'Se colocaron ' + picked.length + ' butaca(s) de la fila indicada.';
                if (picked.length < needed) {
                    msg += ' Solo había ' + picked.length + ' disponible(s); faltan ' + (needed - picked.length) + '.';
                }
                if (maxX > sw || maxY > sh) {
                    msg += ' Parte de la grilla queda fuera del lienzo: reduce tamaños, huecos o usa Ctrl+rueda para alejar.';
                }
                setStatus(msg, picked.length < needed || maxX > sw || maxY > sh ? 'warn' : 'ok');
            }

            function elementLabel(el) {
                if (el.type === 'seat') return el.seat?.label || ('Butaca #' + (el.seat_id ?? ''));
                if (el.type === 'stage') return (el.meta && el.meta.label) ? el.meta.label : 'ESCENARIO';
                return (el.meta && el.meta.label) ? el.meta.label : 'PARLANTE';
            }

            function colorsForType(type) {
                if (type === 'seat') return { fill: '#059669', stroke: '#065f46', text: '#ffffff' };
                if (type === 'stage') return { fill: '#b91c1c', stroke: '#7f1d1d', text: '#ffffff' };
                return { fill: '#d97706', stroke: '#92400e', text: '#ffffff' };
            }

            function sectionPalette(sectionId) {
                const id = Number(sectionId) || 0;
                const palette = [
                    { fill: '#2563eb', stroke: '#1d4ed8', text: '#ffffff' }, // blue
                    { fill: '#a855f7', stroke: '#7e22ce', text: '#ffffff' }, // purple
                    { fill: '#f59e0b', stroke: '#b45309', text: '#111827' }, // amber
                    { fill: '#06b6d4', stroke: '#0e7490', text: '#ffffff' }, // cyan
                    { fill: '#ef4444', stroke: '#b91c1c', text: '#ffffff' }, // red
                    { fill: '#84cc16', stroke: '#4d7c0f', text: '#111827' }, // lime
                    { fill: '#f97316', stroke: '#c2410c', text: '#ffffff' }, // orange
                ];
                return palette[Math.abs(id) % palette.length];
            }

            function findElementIndexById(id) {
                return elements.findIndex(e => Number(e.id) === Number(id));
            }

            function isAdditiveSelectionEvent(evt) {
                return !!(evt && (evt.shiftKey || evt.ctrlKey || evt.metaKey));
            }

            function syncTransformerSelection() {
                if (!layer || !transformer) return;
                const nodes = konvaFindToArray(layer.find('.layout-item')).filter(function(n) {
                    return n && n.attrs && selectedGroupIds.includes(Number(n.attrs._layoutId));
                });
                transformer.nodes(nodes);
            }

            function setSelectionByIds(ids, primaryId) {
                selectedGroupIds = Array.from(new Set((ids || []).map(v => Number(v)).filter(Number.isFinite)));
                const nodes = layer ? konvaFindToArray(layer.find('.layout-item')) : [];
                if (!selectedGroupIds.length) {
                    selectedGroup = null;
                } else {
                    const wantedPrimary = primaryId != null ? Number(primaryId) : Number(selectedGroupIds[0]);
                    selectedGroup = nodes.find(n => n.attrs && Number(n.attrs._layoutId) === wantedPrimary) || nodes.find(n => n.attrs && selectedGroupIds.includes(Number(n.attrs._layoutId))) || null;
                }
                syncTransformerSelection();
                refreshSeatSectionPickerFromSelection();
                refreshSelectionOutline();
                if (layer) layer.draw();
            }

            function getSelectedModel() {
                if (!selectedGroup) return null;
                const idx = findElementIndexById(selectedGroup.attrs._layoutId);
                return idx >= 0 ? elements[idx] : null;
            }

            function refreshSelectionOutline() {
                if (!layer) return;
                const nodes = konvaFindToArray(layer.find('.layout-item'));
                nodes.forEach(function(group) {
                    const outline = group.findOne('.selection-outline');
                    if (!outline) return;
                    const gid = group && group.attrs ? Number(group.attrs._layoutId) : null;
                    const isSelected = gid != null && selectedGroupIds.includes(gid);
                    outline.visible(!!isSelected);
                });
            }

            function applyTransformerForType(type) {
                if (!transformer) return;
                if (type === 'seat') {
                    transformer.keepRatio(true);
                    transformer.enabledAnchors(['top-left', 'top-right', 'bottom-left', 'bottom-right']);
                } else {
                    transformer.keepRatio(false);
                    transformer.enabledAnchors(['top-left', 'top-center', 'top-right', 'middle-right', 'middle-left', 'bottom-left', 'bottom-center', 'bottom-right']);
                }
            }

            function syncModelFromGroup(group) {
                const idx = findElementIndexById(group.attrs._layoutId);
                if (idx < 0) return;
                const el = elements[idx];
                el.x = snapPos(group.x());
                el.y = snapPos(group.y());
                el.w = Math.max(MIN_W, snapPos(group.width() * group.scaleX()));
                el.h = Math.max(MIN_H, snapPos(group.height() * group.scaleY()));
                el.rotation = snapRot(group.rotation());
                group.scaleX(1);
                group.scaleY(1);
                group.width(el.w);
                group.height(el.h);
                group.x(el.x);
                group.y(el.y);
                group.rotation(el.rotation);
                clampGroupToStage(group);
                el.x = snapPos(group.x());
                el.y = snapPos(group.y());
                const rect = group.findOne('Rect');
                const outline = group.findOne('.selection-outline');
                const text = group.findOne('Text');
                if (rect) {
                    rect.width(el.w);
                    rect.height(el.h);
                }
                if (outline) {
                    outline.width(el.w);
                    outline.height(el.h);
                }
                if (text) {
                    text.width(el.w);
                    text.height(el.h);
                    text.text(elementLabel(el));
                    text.fontSize(Math.min(14, Math.max(10, el.h * 0.28)));
                }
            }

            function buildGroup(el) {
                const filterId = sectionViewSelectEl ? (parseInt(sectionViewSelectEl.value, 10) || 0) : 0;
                const seatSectionId = el.type === 'seat' ? resolveSeatSectionIdForLayoutEl(el) : null;
                const seatVisible = (el.type !== 'seat') || (!filterId) || (seatSectionId != null && Number(seatSectionId) === filterId);

                const flatSeatColors = colorBySectionEl && colorBySectionEl.checked;
                const colors = !seatVisible
                    ? { fill: 'rgba(148,163,184,0.12)', stroke: 'rgba(148,163,184,0.45)', text: 'rgba(148,163,184,0.55)' }
                    : (el.type === 'seat'
                        ? (flatSeatColors ? colorsForType('seat') : seatColorsForLayoutElement(el))
                        : colorsForType(el.type));
                const group = new Konva.Group({
                    x: snapPos(Number(el.x ?? 0)),
                    y: snapPos(Number(el.y ?? 0)),
                    width: Number(el.w ?? 48),
                    height: Number(el.h ?? 48),
                    rotation: snapRot(Number(el.rotation ?? 0)),
                    draggable: seatVisible && !panMode,
                    listening: seatVisible,
                    name: 'layout-item',
                });
                group.attrs._layoutId = el.id;

                const rect = new Konva.Rect({
                    width: group.width(),
                    height: group.height(),
                    cornerRadius: 8,
                    fill: colors.fill,
                    stroke: colors.stroke,
                    strokeWidth: 1,
                    listening: seatVisible,
                });
                const selectionOutline = new Konva.Rect({
                    width: group.width(),
                    height: group.height(),
                    cornerRadius: 8,
                    stroke: '#ffffff',
                    strokeWidth: 3,
                    dash: [6, 3],
                    listening: false,
                    visible: false,
                    name: 'selection-outline',
                });
                const text = new Konva.Text({
                    width: group.width(),
                    height: group.height(),
                    align: 'center',
                    verticalAlign: 'middle',
                    text: elementLabel(el),
                    fontSize: Math.min(14, Math.max(10, group.height() * 0.28)),
                    fontStyle: 'bold',
                    fill: colors.text,
                    padding: 6,
                    listening: false,
                });
                group.add(rect);
                group.add(selectionOutline);
                group.add(text);

                group.on('dragmove', function() {
                    clampGroupToStage(group);
                });
                group.on('dragend transformend', function() {
                    syncModelFromGroup(group);
                    markDirty();
                });
                group.on('click tap', function(e) {
                    if (panMode) return;
                    e.cancelBubble = true;
                    const gid = Number(group.attrs._layoutId);
                    if (isAdditiveSelectionEvent(e.evt)) {
                        if (selectedGroupIds.includes(gid)) {
                            setSelectionByIds(selectedGroupIds.filter(id => id !== gid), selectedGroup ? Number(selectedGroup.attrs._layoutId) : null);
                        } else {
                            setSelectionByIds(selectedGroupIds.concat([gid]), gid);
                        }
                    } else {
                        setSelectionByIds([gid], gid);
                    }
                    const m = getSelectedModel();
                    applyTransformerForType(m ? m.type : 'stage');
                });
                group.on('dblclick dbltap', function(e) {
                    e.cancelBubble = true;
                    if (!confirm('¿Eliminar este elemento del layout?')) return;
                    const idx = findElementIndexById(group.attrs._layoutId);
                    if (idx >= 0) {
                        elements.splice(idx, 1);
                        setSelectionByIds([], null);
                        normalizeZ();
                        refreshSeatSelect();
                        rebuildScene();
                        markDirty('Elemento eliminado. No olvides guardar.');
                    }
                });

                return group;
            }

            function drawGrid() {
                if (!gridLayer || !stage) return;
                gridLayer.destroyChildren();
                const w = stage.width();
                const h = stage.height();
                for (let x = 0; x <= w; x += GRID) {
                    gridLayer.add(new Konva.Line({
                        points: [x, 0, x, h],
                        stroke: 'rgba(148,163,184,0.22)',
                        strokeWidth: 1,
                        listening: false,
                    }));
                }
                for (let y = 0; y <= h; y += GRID) {
                    gridLayer.add(new Konva.Line({
                        points: [0, y, w, y],
                        stroke: 'rgba(148,163,184,0.22)',
                        strokeWidth: 1,
                        listening: false,
                    }));
                }
                gridLayer.draw();
            }

            function rebuildScene() {
                if (!stage) return;
                const hadSelIds = [...selectedGroupIds];
                const hadSelId = selectedGroup ? selectedGroup.attrs._layoutId : null;
                const itemNodes = konvaFindToArray(layer.find('.layout-item'));
                itemNodes.forEach(function(node) {
                    if (node && typeof node.destroy === 'function') node.destroy();
                });
                if (transformer) {
                    transformer.nodes([]);
                }
                elements.sort((a, b) => (a.z_index ?? 0) - (b.z_index ?? 0));
                elements.forEach(el => layer.add(buildGroup(el)));
                layer.add(transformer);
                refreshSelectionOutline();
                layer.draw();
                drawGrid();
                if (hadSelIds.length) {
                    const nodes = konvaFindToArray(layer.find('.layout-item'));
                    const stillIds = hadSelIds.filter(function(id) { return nodes.some(n => n.attrs && Number(n.attrs._layoutId) === Number(id)); });
                    const primary = stillIds.includes(Number(hadSelId)) ? Number(hadSelId) : (stillIds[0] ?? null);
                    setSelectionByIds(stillIds, primary);
                    const m = getSelectedModel();
                    applyTransformerForType(m ? m.type : 'stage');
                }
            }

            function updateZoomLabel() {
                if (!zoomLabelEl || !stage) return;
                zoomLabelEl.textContent = Math.round(stage.scaleX() * 100) + '%';
            }

            function updatePanModeUI() {
                if (panToggleBtn) {
                    panToggleBtn.classList.toggle('bg-violet-600', panMode);
                    panToggleBtn.classList.toggle('text-white', panMode);
                    panToggleBtn.classList.toggle('hover:bg-violet-500', panMode);
                }
                if (host) {
                    host.style.cursor = panMode ? 'grab' : 'default';
                }
                if (stage) {
                    stage.draggable(!!panMode);
                }
            }

            function zoomToScale(newScale, anchor) {
                if (!stage) return;
                const oldScale = stage.scaleX();
                const clamped = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, newScale));
                if (Math.abs(clamped - oldScale) < 0.001) {
                    updateZoomLabel();
                    return;
                }
                const pos = anchor || { x: stage.width() / 2, y: stage.height() / 2 };
                const mousePointTo = {
                    x: (pos.x - stage.x()) / oldScale,
                    y: (pos.y - stage.y()) / oldScale,
                };
                stage.scale({ x: clamped, y: clamped });
                stage.position({
                    x: pos.x - mousePointTo.x * clamped,
                    y: pos.y - mousePointTo.y * clamped,
                });
                updateZoomLabel();
                layer.batchDraw();
                gridLayer.batchDraw();
            }

            function resetStageView() {
                if (!stage) return;
                stage.scale({ x: 1, y: 1 });
                stage.position({ x: 0, y: 0 });
                updateZoomLabel();
                layer.batchDraw();
                gridLayer.batchDraw();
            }

            function worldPointFromPointer(pointer) {
                const scale = stage.scaleX() || 1;
                return {
                    x: (pointer.x - stage.x()) / scale,
                    y: (pointer.y - stage.y()) / scale,
                };
            }

            function updateCursorCoordsFromEvent() {
                if (!stage || !cursorCoordsEl) return;
                const pointer = stage.getPointerPosition();
                if (!pointer) return;
                const wp = worldPointFromPointer(pointer);
                const x = snapPos(Math.max(0, Math.round(wp.x)));
                const y = snapPos(Math.max(0, Math.round(wp.y)));
                cursorCoordsEl.textContent = 'x:' + x + ' y:' + y;
            }

            function setPickOriginMode(on) {
                pickOriginMode = !!on;
                if (originPickBadgeEl) {
                    originPickBadgeEl.classList.toggle('hidden', !pickOriginMode);
                }
                if (pickOriginBtn) {
                    pickOriginBtn.classList.toggle('bg-violet-600', pickOriginMode);
                    pickOriginBtn.classList.toggle('text-white', pickOriginMode);
                    pickOriginBtn.classList.toggle('border-violet-600', pickOriginMode);
                }
                if (pickOriginMode) {
                    setStatus('Modo origen activo: haz clic en el fondo del lienzo para setear X/Y.', 'warn');
                } else {
                    setStatus('Modo origen desactivado.', 'ok');
                }
            }

            function resizeStageFromHost() {
                if (!stage) return;
                const w = Math.max(1600, Math.floor(host.clientWidth || 0));
                const h = Math.max(CANVAS_H_MIN, Math.min(CANVAS_H_MAX, Math.floor(host.clientHeight || CANVAS_H_MIN)));
                const key = w + 'x' + h;
                if (lastHostSizeKey === key) {
                    return;
                }
                lastHostSizeKey = key;
                stage.width(w);
                stage.height(h);
                drawGrid();
                layer.getChildren().forEach(function(node) {
                    if (node.name && node.name() === 'layout-item') {
                        clampGroupToStage(node);
                    }
                });
                layer.draw();
            }

            function initKonva() {
                if (typeof Konva === 'undefined') {
                    setStatus('No se pudo cargar Konva.js (revisa conexión o bloqueo de CDN).', 'error');
                    return;
                }
                syncSeatsFromLayoutElements();
                const initW = Math.max(1600, Math.floor(host.clientWidth || 0));
                const initH = Math.max(CANVAS_H_MIN, Math.min(CANVAS_H_MAX, Math.floor(host.clientHeight || CANVAS_H_MIN)));
                stage = new Konva.Stage({
                    container: 'layout-konva-host',
                    width: initW,
                    height: initH,
                });
                stage.scale({ x: 1, y: 1 });
                stage.position({ x: 0, y: 0 });
                stage.draggable(false);
                gridLayer = new Konva.Layer({ listening: false });
                stage.add(gridLayer);
                layer = new Konva.Layer();
                stage.add(layer);
                transformer = new Konva.Transformer({
                    rotateEnabled: true,
                    rotationSnaps: [],
                    boundBoxFunc: (oldBox, newBox) => {
                        if (newBox.width < MIN_W || newBox.height < MIN_H) return oldBox;
                        return newBox;
                    },
                });
                selectionRect = new Konva.Rect({
                    fill: 'rgba(124,58,237,0.12)',
                    stroke: '#8b5cf6',
                    strokeWidth: 1,
                    dash: [4, 4],
                    visible: false,
                    listening: false,
                });
                layer.add(selectionRect);
                stage.on('wheel', function(e) {
                    e.evt.preventDefault();
                    const oldScale = stage.scaleX();
                    let pointer = stage.getPointerPosition();
                    if (!pointer) {
                        pointer = { x: stage.width() / 2, y: stage.height() / 2 };
                    }
                    const scaleBy = 1.06;
                    const direction = e.evt.deltaY > 0 ? -1 : 1;
                    const newScale = direction > 0 ? oldScale / scaleBy : oldScale * scaleBy;
                    zoomToScale(newScale, pointer);
                });
                stage.on('mousedown touchstart', function(e) {
                    if (pickOriginMode) return;
                    if (panMode) {
                        if (host) host.style.cursor = 'grabbing';
                        return;
                    }
                    if (e.target !== stage) return;
                    const p = stage.getPointerPosition();
                    if (!p) return;
                    isSelecting = true;
                    selectionRectStart = worldPointFromPointer(p);
                    selectionRect.visible(true);
                    selectionRect.width(0);
                    selectionRect.height(0);
                    selectionRect.position(selectionRectStart);
                    layer.batchDraw();
                });
                stage.on('mousemove touchmove', function(e) {
                    updateCursorCoordsFromEvent();
                    if (!isSelecting || !selectionRectStart) return;
                    const p = stage.getPointerPosition();
                    if (!p) return;
                    const wp = worldPointFromPointer(p);
                    const x = Math.min(selectionRectStart.x, wp.x);
                    const y = Math.min(selectionRectStart.y, wp.y);
                    const w = Math.abs(wp.x - selectionRectStart.x);
                    const h = Math.abs(wp.y - selectionRectStart.y);
                    selectionRect.position({ x, y });
                    selectionRect.width(w);
                    selectionRect.height(h);
                    layer.batchDraw();
                });
                stage.on('mouseup touchend', function(e) {
                    if (panMode) {
                        if (host) host.style.cursor = 'grab';
                        return;
                    }
                    if (!isSelecting) return;
                    const box = selectionRect.getClientRect();
                    selectionRect.visible(false);
                    isSelecting = false;
                    selectionRectStart = null;
                    const nodes = konvaFindToArray(layer.find('.layout-item'));
                    const ids = nodes
                        .filter(function(n) { return Konva.Util.haveIntersection(box, n.getClientRect()); })
                        .map(function(n) { return Number(n.attrs._layoutId); });
                    if (ids.length) {
                        setSelectionByIds(ids, ids[0]);
                        const m = getSelectedModel();
                        applyTransformerForType(m ? m.type : 'stage');
                    } else if (!isAdditiveSelectionEvent(e.evt)) {
                        setSelectionByIds([], null);
                    }
                    layer.batchDraw();
                });
                stage.on('click tap', function(e) {
                    if (panMode) return;
                    if (isSelecting) return;
                    if (e.target === stage) {
                        if (pickOriginMode) {
                            const pointer = stage.getPointerPosition();
                            if (pointer) {
                                const wp = worldPointFromPointer(pointer);
                                const x = snapPos(Math.max(0, Math.round(wp.x)));
                                const y = snapPos(Math.max(0, Math.round(wp.y)));
                                if (bulkStartX) bulkStartX.value = String(x);
                                if (bulkStartY) bulkStartY.value = String(y);
                                setStatus('Origen actualizado: X=' + x + ' Y=' + y + '.', 'ok');
                            }
                            setPickOriginMode(false);
                            return;
                        }
                        if (!isAdditiveSelectionEvent(e.evt)) {
                            setSelectionByIds([], null);
                        }
                    }
                });
                stage.on('mouseenter', function() {
                    updateCursorCoordsFromEvent();
                });
                stage.on('dragstart', function() {
                    if (panMode && host) host.style.cursor = 'grabbing';
                });
                stage.on('dragend', function() {
                    if (panMode && host) host.style.cursor = 'grab';
                });
                lastHostSizeKey = null;
                resizeStageFromHost();
                updateZoomLabel();
                window.addEventListener('resize', resizeStageFromHost);
                if (window.ResizeObserver && host.parentElement) {
                    resizeObserver = new ResizeObserver(function() { resizeStageFromHost(); });
                    resizeObserver.observe(host.parentElement);
                }
                if (colorBySectionEl) {
                    colorBySectionEl.addEventListener('change', function() {
                        rebuildScene();
                        setStatus(colorBySectionEl.checked ? 'Modo un solo color activado.' : 'Colores por sección visibles.', 'ok');
                    });
                }
                if (sectionViewSelectEl) {
                    sectionViewSelectEl.addEventListener('change', function() {
                        setSelectionByIds([], null);
                        rebuildScene();
                        setStatus('Filtro por sección actualizado.', 'ok');
                    });
                }
                if (panToggleBtn) {
                    panToggleBtn.addEventListener('click', function() {
                        panMode = !panMode;
                        if (panMode) {
                            setPickOriginMode(false);
                            setSelectionByIds([], null);
                        }
                        rebuildScene();
                        updatePanModeUI();
                        setStatus(panMode ? 'Modo mano activado: arrastra para desplazar el lienzo.' : 'Modo mano desactivado.', 'ok');
                    });
                }
                updatePanModeUI();
                rebuildScene();
            }

            function cloneElementModel(el) {
                return {
                    id: localId--,
                    type: el.type,
                    seat_id: el.seat_id,
                    x: snapPos(Number(el.x ?? 0) + GRID * 2),
                    y: snapPos(Number(el.y ?? 0) + GRID * 2),
                    w: Number(el.w ?? 48),
                    h: Number(el.h ?? 48),
                    rotation: Number(el.rotation ?? 0),
                    z_index: (elements.reduce((m, x) => Math.max(m, x.z_index || 0), 0) + 1),
                    meta: Object.assign({}, el.meta || {}),
                    seat: el.seat ? Object.assign({}, el.seat) : null,
                };
            }

            function duplicateSelected() {
                const m = getSelectedModel();
                if (!m) {
                    setStatus('Selecciona un elemento para duplicar.', 'error');
                    return;
                }
                if (m.type === 'seat') {
                    setStatus('Las butacas no se pueden duplicar (cada butaca solo aparece una vez). Usa otra butaca desde la lista.', 'error');
                    return;
                }
                const copy = cloneElementModel(m);
                elements.push(copy);
                normalizeZ();
                refreshSeatSelect();
                rebuildScene();
                markDirty('Elemento duplicado.');
            }

            function deleteSelectedElement() {
                if (!selectedGroupIds.length) {
                    setStatus('Selecciona uno o más elementos para eliminar.', 'error');
                    return;
                }
                const amount = selectedGroupIds.length;
                if (!confirm(amount > 1 ? `¿Eliminar los ${amount} elementos seleccionados?` : '¿Eliminar el elemento seleccionado?')) return;
                const idSet = new Set(selectedGroupIds.map(Number));
                const before = elements.length;
                for (let i = elements.length - 1; i >= 0; i--) {
                    if (idSet.has(Number(elements[i].id))) {
                        elements.splice(i, 1);
                    }
                }
                if (elements.length === before) return;
                setSelectionByIds([], null);
                normalizeZ();
                refreshSeatSelect();
                rebuildScene();
                markDirty(amount > 1 ? 'Elementos eliminados. No olvides guardar.' : 'Elemento eliminado. No olvides guardar.');
            }

            function applySectionToSelectedSeat() {
                const selectedSeatModels = elements.filter(function(el) {
                    return selectedGroupIds.includes(Number(el.id)) && el.type === 'seat' && !!el.seat_id;
                });
                if (!selectedSeatModels.length) {
                    setStatus('Selecciona una o más butacas para asignar sección.', 'error');
                    return;
                }
                const raw = seatSectionSelectEl ? seatSectionSelectEl.value : '';
                const sectionId = raw === '' ? null : Number(raw);
                if (sectionId !== null && (!Number.isFinite(sectionId) || !sectionExists(sectionId))) {
                    setStatus('Sección inválida.', 'error');
                    return;
                }
                let updated = 0;
                selectedSeatModels.forEach(function(m) {
                    const seat = seatById(m.seat_id);
                    if (!seat) return;
                    seat.section_id = sectionId;
                    if (m.seat) m.seat.section_id = sectionId;
                    updated += 1;
                });
                if (!updated) {
                    setStatus('No se encontraron butacas válidas en la selección.', 'error');
                    return;
                }
                rebuildScene();
                refreshSelectionOutline();
                markDirty('Sección de butaca actualizada. No olvides guardar.');
                setStatus(sectionId ? `${updated} butaca(s) asignada(s) a sección.` : `${updated} butaca(s) sin sección.`, 'ok');
            }

            btnFront.addEventListener('click', function() {
                const m = getSelectedModel();
                if (!m) { setStatus('Selecciona un elemento.', 'error'); return; }
                const maxZ = elements.reduce((acc, x) => Math.max(acc, x.z_index || 0), 0);
                m.z_index = maxZ + 1;
                normalizeZ();
                rebuildScene();
                markDirty();
            });
            btnBack.addEventListener('click', function() {
                const m = getSelectedModel();
                if (!m) { setStatus('Selecciona un elemento.', 'error'); return; }
                const minZ = elements.reduce((acc, x) => Math.min(acc, x.z_index || 0), Infinity);
                m.z_index = minZ - 1;
                normalizeZ();
                rebuildScene();
                markDirty();
            });
            btnDup.addEventListener('click', duplicateSelected);
            if (btnDelete) {
                btnDelete.addEventListener('click', deleteSelectedElement);
            }
            if (seatSectionApplyBtn) {
                seatSectionApplyBtn.addEventListener('click', applySectionToSelectedSeat);
            }
            if (zoomInBtn) {
                zoomInBtn.addEventListener('click', function() {
                    if (!stage) return;
                    zoomToScale(stage.scaleX() * 1.12, { x: stage.width() / 2, y: stage.height() / 2 });
                });
            }
            if (zoomOutBtn) {
                zoomOutBtn.addEventListener('click', function() {
                    if (!stage) return;
                    zoomToScale(stage.scaleX() / 1.12, { x: stage.width() / 2, y: stage.height() / 2 });
                });
            }
            if (zoomResetBtn) {
                zoomResetBtn.addEventListener('click', function() {
                    resetStageView();
                });
            }
            document.querySelectorAll('.bulk-preset-per-row').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (bulkPerRow) bulkPerRow.value = String(btn.getAttribute('data-per-row') || '10');
                });
            });
            if (pickOriginBtn) {
                pickOriginBtn.addEventListener('click', function() {
                    setPickOriginMode(!pickOriginMode);
                });
            }
            btnReset.addEventListener('click', function() {
                const m = getSelectedModel();
                if (!m) { setStatus('Selecciona un elemento.', 'error'); return; }
                m.rotation = 0;
                rebuildScene();
                markDirty('Rotación reiniciada.');
            });

            document.querySelectorAll('.layout-add').forEach(btn => {
                btn.addEventListener('click', function() {
                    const type = btn.dataset.addType;
                    elements.push({
                        id: localId--,
                        type: type,
                        seat_id: null,
                        x: snapPos(32),
                        y: snapPos(32),
                        w: type === 'stage' ? 220 : 96,
                        h: 56,
                        rotation: 0,
                        z_index: (elements.reduce((m, x) => Math.max(m, x.z_index || 0), 0) + 1),
                        meta: {},
                        seat: null,
                    });
                    normalizeZ();
                    rebuildScene();
                    markDirty('Elemento añadido. No olvides guardar.');
                });
            });

            addSeatBtn.addEventListener('click', function() {
                const seatId = Number(seatSelect.value);
                if (!seatId) {
                    setStatus('Selecciona una butaca en la lista antes de agregar.', 'error');
                    return;
                }
                const seat = seats.find(s => Number(s.id) === seatId);
                if (!seat) {
                    setStatus('Butaca no válida.', 'error');
                    return;
                }
                elements.push({
                    id: localId--,
                    type: 'seat',
                    seat_id: seat.id,
                    x: snapPos(48),
                    y: snapPos(48),
                    w: 52,
                    h: 52,
                    rotation: 0,
                    z_index: (elements.reduce((m, x) => Math.max(m, x.z_index || 0), 0) + 1),
                    meta: {},
                    seat: { id: seat.id, label: seat.label, section_id: seat.section_id != null ? seat.section_id : null },
                });
                normalizeZ();
                refreshSeatSelect();
                rebuildScene();
                markDirty('Butaca añadida. No olvides guardar.');
            });

            if (bulkRowsBtn) {
                bulkRowsBtn.addEventListener('click', insertBulkSeatRows);
            }
            if (bulkMatrixBtn) {
                bulkMatrixBtn.addEventListener('click', insertBulkSeatMatrix);
            }

            saveBtn.addEventListener('click', async function() {
                const seatElems = elements.filter(e => e.type === 'seat');
                if (seatElems.length === 0) {
                    setStatus('Debes colocar al menos una butaca en el layout antes de guardar.', 'error');
                    return;
                }
                const hasStage = elements.some(e => e.type === 'stage');
                if (!hasStage) {
                    if (!confirm('No hay elemento de escenario. ¿Guardar igualmente?')) return;
                }
                saveBtn.disabled = true;
                setStatus('Guardando layout...', 'warn');
                try {
                    const payload = {
                        elements: elements.map((el, idx) => ({
                            id: Number(el.id) > 0 ? Number(el.id) : null,
                            type: el.type,
                            seat_id: el.type === 'seat' ? Number(el.seat_id) : null,
                            x: Number(el.x ?? 0),
                            y: Number(el.y ?? 0),
                            w: Number(el.w ?? 48),
                            h: Number(el.h ?? 48),
                            rotation: Number(el.rotation ?? 0),
                            z_index: Number(el.z_index ?? idx),
                            meta: el.meta || {},
                        })),
                        seat_sections: buildSeatSectionsPayload(),
                        canvas_width: stage ? Math.round(stage.width()) : null,
                        canvas_height: stage ? Math.round(stage.height()) : null,
                    };
                    const res = await fetch(saveUrl, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        setStatus(data.message || 'No se pudo guardar.', 'error');
                        return;
                    }
                    elements.splice(0, elements.length, ...(data.elements || []));
                    if (Array.isArray(data.seats)) {
                        seats.splice(0, seats.length, ...data.seats);
                    }
                    syncSeatsFromLayoutElements();
                    dirty = false;
                    refreshSeatSelect();
                    refreshSeatSectionPickerFromSelection();
                    rebuildScene();
                    setStatus(data.message || 'Layout guardado.', 'ok');
                } catch (err) {
                    setStatus('Error de red al guardar layout.', 'error');
                } finally {
                    saveBtn.disabled = false;
                }
            });

            window.addEventListener('keydown', function(e) {
                const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;

                if (e.key === 'Escape') {
                    setSelectionByIds([], null);
                    return;
                }

                if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'C')) {
                    const m = getSelectedModel();
                    if (!m) return;
                    clipboard = JSON.parse(JSON.stringify(m));
                    setStatus('Copiado al portapapeles interno.', 'ok');
                    e.preventDefault();
                    return;
                }
                if ((e.ctrlKey || e.metaKey) && (e.key === 'v' || e.key === 'V')) {
                    if (!clipboard) return;
                    const copy = cloneElementModel(clipboard);
                    if (copy.type === 'seat') {
                        const used = new Set(elements.filter(x => x.type === 'seat' && x.seat_id).map(x => Number(x.seat_id)));
                        if (used.has(Number(copy.seat_id))) {
                            setStatus('Esa butaca ya está en el layout. Elige otra butaca o elimina la existente.', 'error');
                            return;
                        }
                    }
                    elements.push(copy);
                    normalizeZ();
                    refreshSeatSelect();
                    rebuildScene();
                    markDirty('Pegado desde portapapeles interno.');
                    e.preventDefault();
                    return;
                }
                if ((e.ctrlKey || e.metaKey) && (e.key === 'd' || e.key === 'D')) {
                    duplicateSelected();
                    e.preventDefault();
                    return;
                }

                if ((e.key === 'Delete' || e.key === 'Backspace') && selectedGroupIds.length > 0) {
                    e.preventDefault();
                    deleteSelectedElement();
                }
            });

            refreshSeatSelect();
            initKonva();
            normalizeZ();
            rebuildScene();
            if (elements.length === 0) {
                setStatus('Aún no hay elementos. Añade escenario, parlantes y butacas.', 'warn');
            }
            window.addEventListener('beforeunload', function(e) {
                if (!dirty) return;
                e.preventDefault();
                e.returnValue = '';
            });

            (function ribbonCollapse() {
                const root = document.getElementById('layout-ribbon-root');
                const body = document.getElementById('layout-ribbon-body');
                const btn = document.getElementById('layout-ribbon-toggle');
                const label = document.getElementById('layout-ribbon-toggle-text');
                const icon = document.getElementById('layout-ribbon-toggle-icon');
                if (!root || !body || !btn || !label || !icon) return;

                const LS_KEY = 'venueLayoutEditorRibbonCollapsed';

                function apply(collapsed) {
                    body.classList.toggle('hidden', collapsed);
                    btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    label.textContent = collapsed ? 'Mostrar cinta' : 'Ocultar cinta';
                    icon.classList.toggle('rotate-180', !collapsed);
                    try {
                        localStorage.setItem(LS_KEY, collapsed ? '1' : '0');
                    } catch (e) { /* ignore */ }
                }

                let startCollapsed = false;
                try {
                    startCollapsed = localStorage.getItem(LS_KEY) === '1';
                } catch (e) { /* ignore */ }
                apply(startCollapsed);

                btn.addEventListener('click', function() {
                    apply(!body.classList.contains('hidden'));
                });

                const LS_SECTIONS = 'venueLayoutRibbonSections';

                function loadRibbonSections() {
                    let raw;
                    try {
                        raw = localStorage.getItem(LS_SECTIONS);
                    } catch (e) {
                        return;
                    }
                    if (!raw) return;
                    let o;
                    try {
                        o = JSON.parse(raw);
                    } catch (e) {
                        return;
                    }
                    if (!o || typeof o !== 'object') return;
                    document.querySelectorAll('details.layout-ribbon-section').forEach(function(d) {
                        const id = d.getAttribute('data-ribbon-section');
                        if (!id || !Object.prototype.hasOwnProperty.call(o, id)) return;
                        if (o[id]) d.setAttribute('open', '');
                        else d.removeAttribute('open');
                    });
                }

                function saveRibbonSections() {
                    const o = {};
                    document.querySelectorAll('details.layout-ribbon-section').forEach(function(d) {
                        const id = d.getAttribute('data-ribbon-section');
                        if (id) o[id] = d.open;
                    });
                    try {
                        localStorage.setItem(LS_SECTIONS, JSON.stringify(o));
                    } catch (e) { /* ignore */ }
                }

                body.addEventListener('toggle', function(e) {
                    if (!(e.target instanceof HTMLDetailsElement) || !e.target.classList.contains('layout-ribbon-section')) return;
                    saveRibbonSections();
                }, true);

                loadRibbonSections();
            })();
        })();
    </script>
</div>
@endsection
