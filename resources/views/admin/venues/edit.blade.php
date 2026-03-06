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

        <div class="rounded-xl border-2 border-violet-200/60 dark:border-violet-700/50 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Secciones (opcional)</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Puedes dividir el lugar en sectores: con butacas (asignando filas) o sin butacas (entrada general con capacidad). En cada evento podrás activar qué secciones usar y el precio.</p>
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
                        <div class="section-rows flex gap-2 items-end" style="{{ !$section->has_seats ? 'display:none' : '' }}">
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
                    '<div class="section-rows flex gap-2 items-end"><div><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Fila desde</label>' +
                    '<input type="number" name="sections[' + sectionIndex + '][row_start]" min="1" max="' + maxRow + '" placeholder="1" class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm"></div>' +
                    '<div><label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Fila hasta</label>' +
                    '<input type="number" name="sections[' + sectionIndex + '][row_end]" min="1" max="' + maxRow + '" placeholder="' + maxRow + '" class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm"></div></div>' +
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
@endsection
