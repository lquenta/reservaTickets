@extends('layouts.app')

@section('title', 'Checkout - ' . $event->name)

@section('content')
{{-- Indicador de pasos del checkout (responsive: texto corto en móvil, completo en sm+) --}}
<div class="max-w-2xl mx-auto mb-6 sm:mb-8 px-1">
    <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-2 sm:gap-x-4">
        <div class="flex items-center gap-2 min-w-0">
            <span class="flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-full bg-[#e50914] text-xs sm:text-sm font-bold text-white">1</span>
            <span class="text-xs sm:text-sm font-medium text-[#e50914]"><span class="sm:hidden">Butacas / datos</span><span class="hidden sm:inline">Elige butacas / datos</span></span>
        </div>
        <div class="h-px w-8 sm:w-12 bg-red-900/50 shrink-0" aria-hidden="true"></div>
        <div class="flex items-center gap-2 min-w-0">
            <span class="flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-full border-2 border-red-900/60 bg-black/40 text-xs sm:text-sm font-medium text-white/60">2</span>
            <span class="text-xs sm:text-sm text-white/50"><span class="sm:hidden">Comprobante</span><span class="hidden sm:inline">Comprobante de pago</span></span>
        </div>
    </div>
</div>

{{-- Helpers de escala del plano (checkout con y sin secciones) --}}
<script>
    (function () {
        const LAYOUT_MAP_CONTENT_PAD = 16;
        function computeLayoutContentBoundsFromElements(els) {
            if (!Array.isArray(els) || !els.length) return null;
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            for (let i = 0; i < els.length; i++) {
                const el = els[i];
                const x = Number(el.x) || 0;
                const y = Number(el.y) || 0;
                const w = Math.max(8, Number(el.w) || 48);
                const h = Math.max(8, Number(el.h) || 48);
                if (x < minX) minX = x;
                if (y < minY) minY = y;
                if (x + w > maxX) maxX = x + w;
                if (y + h > maxY) maxY = y + h;
            }
            if (!Number.isFinite(minX) || !Number.isFinite(minY)) return null;
            return { minX, minY, maxX, maxY };
        }
        window.LAYOUT_MAP_CONTENT_PAD = LAYOUT_MAP_CONTENT_PAD;
        window.computeLayoutContentBoundsFromElements = computeLayoutContentBoundsFromElements;
        window.LAYOUT_CHECKOUT_ZOOM_PAD = 16;
        window.LAYOUT_CHECKOUT_MIN_SCALE = 0.12;
        window.layoutCheckoutFitScale = function (el, dw, dh, pad) {
            if (!el || dw <= 0 || dh <= 0) return 1;
            const p = pad != null ? pad : window.LAYOUT_CHECKOUT_ZOOM_PAD;
            const cw = Math.max(1, el.clientWidth - p);
            const ch = Math.max(1, el.clientHeight - p);
            let fit = Math.min(1, cw / dw, ch / dh);
            if (!Number.isFinite(fit) || fit <= 0) fit = 1;
            return Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, Math.min(1, fit));
        };
    })();
</script>

@if(!empty($sectionsData))
    {{-- Con secciones: por sector con/sin butacas --}}
    @php
        $maxSeats = \App\Services\ReservationService::MAX_SEATS;
    @endphp
    @php
        $sectionIdsWithoutSeats = collect($sectionsData)->where('has_seats', false)->pluck('id')->values()->all();
        $sectionSeatAvailableIds = collect($sectionsData)
            ->where('has_seats', true)
            ->flatMap(fn ($s) => $s['availableSeatIds'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $layoutElementsData = $layoutElements ?? [];
        $hasCustomLayoutBlade = !empty($layoutElementsData);
        $seatSectionsForLegend = collect($sectionsData)->where('has_seats', true)->values();
        $seatsMapFlat = $seatsMap ?? [];
        $seatIdToPrice = $seatIdToPrice ?? [];
        $sectionIdToPrice = $sectionIdToPrice ?? [];
        $sectionIdToName = $sectionIdToName ?? [];
        $layoutCanvasData = $layoutCanvas ?? ['width' => null, 'height' => null];
    @endphp
    <div class="mx-auto w-full min-w-0 max-w-full px-2 sm:max-w-4xl sm:px-0" x-data="reservationSections({
        maxSeats: {{ $maxSeats }},
        sectionIdsWithoutSeats: {{ json_encode($sectionIdsWithoutSeats) }},
        sectionSeatAvailableIds: {{ json_encode($sectionSeatAvailableIds) }},
        layoutElements: {{ json_encode($layoutElementsData) }},
        layoutCanvas: {{ json_encode($layoutCanvasData) }},
        sectionsWithSeats: {{ json_encode(collect($sectionsData)->where('has_seats', true)->map(fn ($s) => ['id' => (int) $s['id'], 'name' => (string) $s['name'], 'price' => $s['price'] ?? null, 'palette' => $s['palette'] ?? null])->values()->all()) }},
        seatsMap: {{ json_encode($seatsMapFlat) }},
        seatIdToPrice: {{ json_encode($seatIdToPrice) }},
        sectionIdToPrice: {{ json_encode($sectionIdToPrice) }},
        sectionIdToName: {{ json_encode($sectionIdToName) }},
        oldSeatIds: {{ json_encode(array_map('intval', (array) old('seat_ids', []))) }},
        oldSectionQuantities: {{ json_encode(old('section_quantities', [])) }},
        oldSingleName: {{ old('single_name', true) ? 'true' : 'false' }},
        oldNames: {{ json_encode(array_merge([0 => ''], array_map(fn ($i) => old("holder_name_{$i}", ''), range(1, $maxSeats)))) }}
    })">
        <h1 class="font-display text-2xl sm:text-3xl font-bold text-[#e50914] tracking-widest mb-2">CHECKOUT — PASO 1</h1>
        <p class="text-lg sm:text-xl text-white/80 mb-2">{{ $event->name }}</p>
        <p class="text-white/60 text-sm mb-4 sm:mb-6">Elige butacas y/o entradas por sección. Máximo {{ $maxSeats }} entradas en total.</p>

        <form id="reservation-form-sections" method="POST" action="{{ route('reservations.store') }}" class="space-y-8">
            @csrf
            <input type="hidden" name="event_id" value="{{ $event->id }}">

            @if($errors->any())
            <div class="rounded-xl border-2 border-red-500 bg-red-900/30 p-5 text-red-200" role="alert">
                <p class="font-semibold mb-2">No se pudo continuar. Revisa los siguientes puntos:</p>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @error('event_id')<p class="text-sm text-red-400">{{ $message }}</p>@enderror

            <template x-if="hasCustomLayout()">
                <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-5 sm:p-6">
                    <p class="text-white/80 text-sm font-medium text-center">Elige tus butacas en el plano</p>
                    <p class="text-white/60 text-xs mt-1 text-center max-w-xl mx-auto">Cada color corresponde a una sección. Filtra con el selector para enfocar una zona (el resto se atenúa). Las entradas sin butaca se eligen más abajo.</p>
                    <div class="mb-3 flex flex-wrap items-center justify-center gap-2 rounded-lg border border-red-900/50 bg-black/50 px-2 py-2 text-white shadow-inner">
                        <span class="w-11 text-center font-mono text-xs tabular-nums text-white/90" x-text="layoutZoomPercent() + '%'"></span>
                        <button type="button" class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-2 text-lg font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomOut()" title="Alejar plano" aria-label="Alejar plano">−</button>
                        <button type="button" class="inline-flex h-9 items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-3 text-xs font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomResetFit()" title="Encajar al espacio" aria-label="Encajar plano al espacio">Encajar</button>
                        <button type="button" class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-2 text-lg font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomIn()" title="Acercar plano" aria-label="Acercar plano">+</button>
                        <span class="hidden text-[10px] text-white/45 sm:inline">Ctrl + rueda</span>
                    </div>
                    <div class="mt-5 mb-4 w-full max-w-xl mx-auto">
                        <label for="layout-section-view-select" class="sr-only">Sección en el plano</label>
                        <select id="layout-section-view-select"
                                x-model.number="selectedSeatSectionId"
                                class="w-full rounded-xl border-2 border-red-900/50 bg-black/70 px-4 py-3.5 sm:py-4 text-center text-base sm:text-lg font-semibold text-white shadow-inner focus:outline-none focus:ring-2 focus:ring-[#e50914] focus:border-[#e50914] cursor-pointer appearance-none bg-[length:1.25rem] bg-[right_0.75rem_center] bg-no-repeat pr-10"
                                style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%23fca5a5%22%3E%3Cpath stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 9l-7 7-7-7%22/%3E%3C/svg%3E');">
                            <option value="0">Todas las secciones</option>
                            <template x-for="s in sectionsWithSeats" :key="s.id">
                                <option :value="s.id" x-text="formatSectionOptionLabel(s)"></option>
                            </template>
                        </select>
                    </div>
                    <div x-ref="layoutViewport"
                         @resize.window="recalcLayoutViewportScale()"
                         class="relative w-full min-h-[200px] max-h-[min(78dvh,900px)] touch-manipulation overflow-auto overscroll-contain rounded-xl border border-red-900/40 bg-[radial-gradient(circle,_rgba(255,255,255,0.12)_1px,_transparent_1px)] bg-[size:16px_16px] p-2 sm:max-h-[min(86vh,90dvh)]">
                        <div class="relative mx-auto shrink-0" :style="layoutScaledHostStyle">
                            <div class="relative" :style="layoutScaledStageStyle">
                                <template x-for="el in sortedLayoutElements" :key="el.id">
                                    <div class="absolute isolate" :style="layoutElementWrapperStyle(el) + 'pointer-events:none;'">
                                        <template x-if="layoutElType(el) === 'seat'">
                                            <button type="button"
                                                    class="absolute inset-0 z-10 rounded-md text-[9px] sm:text-[10px] font-bold px-0.5 sm:px-1 transition border-2 flex items-center justify-center leading-none overflow-hidden text-center pointer-events-auto"
                                                    :class="layoutSeatClass(el)"
                                                    :style="layoutSeatFaceStyle(el)"
                                                    :disabled="!canSelectLayoutSeat(el)"
                                                    @click="toggleLayoutSeat(el)"
                                                    :title="el.seat ? `Butaca ${el.seat.label}` : 'Butaca'">
                                                <span class="truncate max-w-full" x-text="el.seat ? el.seat.label : ''"></span>
                                            </button>
                                        </template>
                                        <template x-if="layoutElType(el) === 'stage'">
                                            <div class="absolute inset-0 z-0 flex items-center justify-center rounded-md border border-red-500/40 bg-red-700 px-0.5 text-white shadow-md pointer-events-none overflow-hidden"
                                                 :style="layoutStageSpeakerFaceStyle(el)">
                                                <span class="max-h-full overflow-hidden text-center text-[8px] font-semibold uppercase leading-tight sm:text-[10px]" x-text="(el.meta && el.meta.label) ? el.meta.label : 'ESCENARIO'"></span>
                                            </div>
                                        </template>
                                        <template x-if="layoutElType(el) === 'speaker'">
                                            <div class="absolute inset-0 z-0 flex items-center justify-center rounded-md border border-amber-400/40 bg-amber-600 px-0.5 text-white shadow-md pointer-events-none overflow-hidden"
                                                 :style="layoutStageSpeakerFaceStyle(el)">
                                                <span class="max-h-full overflow-hidden text-center text-[8px] font-semibold uppercase leading-tight sm:text-[10px]" x-text="(el.meta && el.meta.label) ? el.meta.label : 'PARLANTE'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            @if($hasCustomLayoutBlade && $seatSectionsForLegend->isNotEmpty())
                <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-semibold text-[#e50914] mb-1">Precios por sector (plano)</h2>
                    <p class="text-white/60 text-sm mb-4">La selección es en el plano de arriba; aquí solo referencia de precio por zona.</p>
                    <ul class="divide-y divide-red-900/30 rounded-xl border border-red-900/40 overflow-hidden">
                        @foreach($seatSectionsForLegend as $sec)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 bg-black/30 text-white/90">
                                <span class="font-medium">{{ $sec['name'] }}</span>
                                @if(isset($sec['price']) && $sec['price'] !== null && $sec['price'] > 0)
                                    <span class="text-white/70 text-sm tabular-nums">{{ number_format((float) $sec['price'], 2) }} Bs</span>
                                @else
                                    <span class="text-white/50 text-sm">—</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @foreach($sectionsData as $section)
                @if(!empty($section['has_seats']) && $hasCustomLayoutBlade)
                    @continue
                @endif
                <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-semibold text-[#e50914] mb-1">{{ $section['name'] }}</h2>
                    @if(isset($section['price']) && $section['price'] !== null && $section['price'] > 0)
                        <p class="text-white/60 text-sm mb-4">Precio: {{ number_format($section['price'], 2) }} Bs</p>
                    @endif
                    @if($section['has_seats'])
                        <p class="text-white/70 text-sm mb-3">
                            <template x-if="hasCustomLayout()">
                                <span>Selecciona las butacas de esta sección directamente en el <strong>plano</strong> de arriba.</span>
                            </template>
                            <template x-if="!hasCustomLayout()">
                                <span>Elige butacas (máx. {{ $maxSeats }} en total entre todas las secciones).</span>
                            </template>
                        </p>
                        @php
                            $seatsByRow = $section['seats']->groupBy('row');
                            $sectionAvailableIds = $section['availableSeatIds'];
                            $sectionMaxCols = $seatsByRow->isEmpty() ? 1 : $seatsByRow->max(fn ($r) => $r->count());
                        @endphp
                        {{-- Si hay layout WYSIWYG, evitamos mostrar “segundo plano” por sección --}}
                        <div class="flex flex-col gap-2 items-center section-seat-plan" x-show="!hasCustomLayout()" style="--section-seat-size: clamp(0.875rem, 6vw, 2.5rem);">
                            {{-- Escenario: misma fila — PARLANTE (círculo) en col 1 y última, línea + ESCENARIO centrada entre ambos --}}
                            <div class="flex gap-2 items-end flex-nowrap mb-1">
                                <span class="shrink-0" style="width: var(--section-seat-size); height: var(--section-seat-size);" aria-hidden="true"></span>
                                <div class="flex items-end gap-2 shrink-0 min-w-0" style="width: calc(var(--section-seat-size) * {{ $sectionMaxCols }} + ({{ $sectionMaxCols }} - 1) * 0.5rem);">
                                    <div class="flex flex-col items-center justify-center rounded-lg border border-amber-600/50 bg-amber-900/20 shrink-0 py-1 px-1.5 gap-0.5" style="width: var(--section-seat-size); min-height: var(--section-seat-size);" role="img" aria-label="Parlante">
                                        <span class="text-[9px] sm:text-[10px] font-semibold text-amber-400/90 uppercase leading-tight"><span class="sm:hidden">P</span><span class="hidden sm:inline">PARLANTE</span></span>
                                    </div>
                                    <div class="flex-1 flex flex-col items-center justify-end gap-0.5 min-w-0 pb-0.5">
                                        <div class="w-full rounded-sm bg-red-700 min-h-[3px]" style="height: 3px;" role="img" aria-label="Línea de escenario"></div>
                                        <span class="text-[10px] font-medium text-red-400 uppercase tracking-wider">ESCENARIO</span>
                                    </div>
                                    <div class="flex flex-col items-center justify-center rounded-lg border border-amber-600/50 bg-amber-900/20 shrink-0 py-1 px-1.5 gap-0.5" style="width: var(--section-seat-size); min-height: var(--section-seat-size);" role="img" aria-label="Parlante">
                                        <span class="text-[9px] sm:text-[10px] font-semibold text-amber-400/90 uppercase leading-tight"><span class="sm:hidden">P</span><span class="hidden sm:inline">PARLANTE</span></span>
                                    </div>
                                </div>
                            </div>
                            @foreach($seatsByRow as $row => $rowSeats)
                                @php $rowLetter = $rowSeats->first()->row_letter ?? chr(64 + (int)$row); @endphp
                                <div class="flex gap-2 items-center justify-center">
                                    <span class="shrink-0 flex items-center justify-center font-bold text-[#e50914]" style="width: var(--section-seat-size); height: var(--section-seat-size); font-size: min(0.875rem, var(--section-seat-size)); line-height: 1;">{{ $rowLetter }}</span>
                                    @foreach($rowSeats as $seat)
                                        <button type="button"
                                                class="seat-btn rounded-lg flex items-center justify-center font-mono transition disabled:opacity-70 disabled:cursor-not-allowed shrink-0"
                                                style="width: var(--section-seat-size); height: var(--section-seat-size); min-width: var(--section-seat-size); font-size: min(0.875rem, var(--section-seat-size)); line-height: 1;"
                                                :class="sectionSeatClass({{ $seat->id }}, {{ json_encode($section['availableSeatIds']) }})"
                                                :disabled="!canSelectSectionSeat({{ $seat->id }}, {{ json_encode($section['availableSeatIds']) }})"
                                                data-seat-id="{{ $seat->id }}"
                                                data-section-id="{{ $section['id'] }}"
                                                @click="toggleSeat({{ $seat->id }}, {{ json_encode($section['availableSeatIds']) }})">
                                            {{ $seat->number }}
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-white/70 text-sm mb-3">Entrada general — {{ $section['availableCapacity'] }} disponibles</p>
                        <div class="flex items-center gap-4">
                            <label class="text-white/80">Cantidad</label>
                            <select name="section_quantities[{{ $section['id'] }}]" x-model.number="sectionQuantities[{{ $section['id'] }}]" class="rounded-xl border border-red-900/50 bg-black/60 px-4 py-2 text-white">
                                @for($q = 0; $q <= min($section['availableCapacity'] ?? 0, $maxSeats); $q++)
                                    <option value="{{ $q }}">{{ $q }}</option>
                                @endfor
                            </select>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-5 sm:p-6">
                <p class="text-white/80 mb-2" x-show="totalTickets > 0">Total: <strong x-text="totalTickets"></strong> entrada(s).</p>
                <p class="cost-total-block text-white/90 mb-2" x-show="totalTickets > 0 && totalCost > 0">Costo total: <span class="cost-total-price block mt-1" x-text="'Bs ' + (typeof totalCost === 'number' ? totalCost.toFixed(2) : '0.00')"></span></p>
                <template x-for="id in selectedSeatIds" :key="id">
                    <input type="hidden" name="seat_ids[]" :value="id">
                </template>
            </div>

            <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-5 sm:p-6 space-y-4">
                <p class="block text-sm font-medium text-white/80">Nombres en los tickets</p>
                <div class="flex flex-col gap-3 sm:flex-row sm:gap-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="single_name" value="1" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                        <span class="text-white/70">Un nombre para todos</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="single_name" value="0" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                        <span class="text-white/70">Un nombre por ticket</span>
                    </label>
                </div>
                <div x-show="singleName === '1' || singleName === true">
                    <label for="holder_name" class="block text-sm font-medium text-white/80 mb-1">Nombre para todos</label>
                    <input id="holder_name" type="text" name="holder_name" x-model="holderName" maxlength="255"
                           class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white">
                </div>
                <div x-show="singleName === '0' || singleName === false" x-cloak>
                    <p class="text-white/60 text-sm mb-3">Asigna el nombre a cada entrada (butaca o sección):</p>
                    <template x-for="(item, index) in ticketItems" :key="index">
                        <div class="mb-4 p-4 rounded-xl border border-red-900/30 bg-black/40">
                            <label :for="'holder_name_'+ (index+1)" class="block text-sm font-medium text-[#e50914] mb-1" x-text="'Ticket ' + (index+1) + ' — ' + item.label"></label>
                            <input :id="'holder_name_'+ (index+1)" type="text" :name="'holder_name_' + (index+1)" maxlength="255"
                                   :value="holderNames[index+1] || ''"
                                   @input="holderNames[index+1] = $event.target.value"
                                   class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40">
                        </div>
                    </template>
                </div>
            </div>

            @if(config('services.recaptcha.site_key'))
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            @endif
            @error('g-recaptcha-response')<p class="text-sm text-red-400">{{ $message }}</p>@enderror

            <p class="text-amber-200/90 text-sm" x-show="totalTickets < 1">Elige al menos una entrada (butaca o cantidad en sección) para continuar.</p>
            <button type="submit"
                    class="w-full rounded-xl bg-[#e50914] px-5 py-4 text-white font-bold hover:bg-red-600 transition disabled:opacity-50"
                    :disabled="totalTickets < 1">
                Continuar al paso 2 — Comprobante de pago
            </button>
        </form>
    </div>
    <script>
        function reservationSections(config) {
            const maxSeats = config.maxSeats || 12;
            const seatsMap = config.seatsMap || {};
            const seatIdToPrice = config.seatIdToPrice || {};
            const sectionIdToPrice = config.sectionIdToPrice || {};
            const sectionIdToName = config.sectionIdToName || {};
            const sectionIdsWithoutSeats = config.sectionIdsWithoutSeats || [];
            const sectionSeatAvailableIds = Array.isArray(config.sectionSeatAvailableIds) ? config.sectionSeatAvailableIds.map(v => parseInt(v, 10)) : [];
            const sectionsWithSeats = Array.isArray(config.sectionsWithSeats) ? config.sectionsWithSeats : [];
            const lc = config.layoutCanvas || {};
            const layoutCanvasW = lc.width != null && Number(lc.width) > 0 ? Number(lc.width) : null;
            const layoutCanvasH = lc.height != null && Number(lc.height) > 0 ? Number(lc.height) : null;
            const sectionQuantities = {};
            sectionIdsWithoutSeats.forEach(id => {
                sectionQuantities[id] = (config.oldSectionQuantities && (config.oldSectionQuantities[id] != null ? config.oldSectionQuantities[id] : config.oldSectionQuantities[String(id)])) || 0;
            });
            return {
                selectedSeatIds: Array.isArray(config.oldSeatIds) ? config.oldSeatIds : [],
                layoutElements: Array.isArray(config.layoutElements) ? config.layoutElements : [],
                sectionsWithSeats: sectionsWithSeats,
                selectedSeatSectionId: 0,
                sectionQuantities: sectionQuantities,
                singleName: config.oldSingleName !== false,
                holderName: (config.oldNames && config.oldNames[1]) || '',
                holderNames: config.oldNames || {},
                _layoutViewportScale: 1,
                _layoutViewportUserScale: null,
                _layoutViewportRo: null,
                _layoutCheckoutWheelBound: false,
                _layoutOrientationHandler: null,
                init() {
                    this.$nextTick(() => this.setupLayoutViewportObserver());
                },
                setupLayoutViewportObserver() {
                    const el = this.$refs.layoutViewport;
                    if (!el || this._layoutViewportRo) {
                        return;
                    }
                    const self = this;
                    this._layoutViewportRo = new ResizeObserver(() => this.recalcLayoutViewportScale());
                    this._layoutViewportRo.observe(el);
                    this.recalcLayoutViewportScale();
                    if (!this._layoutCheckoutWheelBound) {
                        this._layoutCheckoutWheelBound = true;
                        el.addEventListener('wheel', function(e) {
                            if (!(e.ctrlKey || e.metaKey)) {
                                return;
                            }
                            e.preventDefault();
                            if (e.deltaY > 0) {
                                self.layoutZoomOut();
                            } else {
                                self.layoutZoomIn();
                            }
                        }, { passive: false });
                        this._layoutOrientationHandler = function() {
                            setTimeout(function() {
                                self.recalcLayoutViewportScale();
                            }, 200);
                        };
                        window.addEventListener('orientationchange', this._layoutOrientationHandler);
                    }
                },
                recalcLayoutViewportScale() {
                    const el = this.$refs.layoutViewport;
                    if (!el) {
                        return;
                    }
                    const pad = window.LAYOUT_CHECKOUT_ZOOM_PAD;
                    const dw = this.layoutDesignWidth;
                    const dh = this.layoutDesignHeight;
                    const fit = window.layoutCheckoutFitScale(el, dw, dh, pad);
                    const u = this._layoutViewportUserScale;
                    if (u == null || !Number.isFinite(Number(u))) {
                        this._layoutViewportScale = fit;
                    } else {
                        this._layoutViewportScale = Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, Math.min(Number(u), 1));
                    }
                },
                layoutZoomPercent() {
                    return Math.round((Number(this._layoutViewportScale) || 1) * 100);
                },
                layoutZoomOut() {
                    const cur = this._layoutViewportUserScale != null ? Number(this._layoutViewportUserScale) : Number(this._layoutViewportScale);
                    this._layoutViewportUserScale = Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, cur / 1.2);
                    this.recalcLayoutViewportScale();
                },
                layoutZoomIn() {
                    const cur = this._layoutViewportUserScale != null ? Number(this._layoutViewportUserScale) : Number(this._layoutViewportScale);
                    this._layoutViewportUserScale = Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, Math.min(1, cur * 1.2));
                    this.recalcLayoutViewportScale();
                },
                layoutZoomResetFit() {
                    this._layoutViewportUserScale = null;
                    const vp = this.$refs.layoutViewport;
                    if (vp) {
                        vp.scrollLeft = 0;
                        vp.scrollTop = 0;
                    }
                    this.recalcLayoutViewportScale();
                },
                inferLayoutCanvasWidth() {
                    const els = this.layoutElements;
                    if (!Array.isArray(els) || !els.length) {
                        return 960;
                    }
                    let m = 320;
                    for (let i = 0; i < els.length; i++) {
                        const el = els[i];
                        const r = (Number(el.x) || 0) + Math.max(8, Number(el.w) || 48) + 48;
                        if (r > m) {
                            m = r;
                        }
                    }
                    return Math.ceil(m);
                },
                inferLayoutCanvasHeight() {
                    const els = this.layoutElements;
                    if (!Array.isArray(els) || !els.length) {
                        return 640;
                    }
                    let m = 420;
                    for (let i = 0; i < els.length; i++) {
                        const el = els[i];
                        const r = (Number(el.y) || 0) + Math.max(8, Number(el.h) || 48) + 48;
                        if (r > m) {
                            m = r;
                        }
                    }
                    return Math.ceil(m);
                },
                getLayoutContentBounds() {
                    return window.computeLayoutContentBoundsFromElements(this.layoutElements);
                },
                get layoutDesignWidth() {
                    const b = this.getLayoutContentBounds();
                    if (b) {
                        return Math.max(200, Math.ceil(b.maxX - b.minX + 2 * window.LAYOUT_MAP_CONTENT_PAD));
                    }
                    if (layoutCanvasW != null) {
                        return layoutCanvasW;
                    }
                    return this.inferLayoutCanvasWidth();
                },
                get layoutDesignHeight() {
                    const b = this.getLayoutContentBounds();
                    if (b) {
                        return Math.max(200, Math.ceil(b.maxY - b.minY + 2 * window.LAYOUT_MAP_CONTENT_PAD));
                    }
                    if (layoutCanvasH != null) {
                        return layoutCanvasH;
                    }
                    return this.inferLayoutCanvasHeight();
                },
                get layoutScaledHostStyle() {
                    const s = this._layoutViewportScale;
                    const dw = this.layoutDesignWidth;
                    const dh = this.layoutDesignHeight;
                    return 'width:' + (dw * s) + 'px;height:' + (dh * s) + 'px;';
                },
                get layoutScaledStageStyle() {
                    const s = this._layoutViewportScale;
                    const dw = this.layoutDesignWidth;
                    const dh = this.layoutDesignHeight;
                    return 'position:absolute;left:0;top:0;width:' + dw + 'px;height:' + dh + 'px;transform:scale(' + s + ');transform-origin:top left;';
                },
                hasCustomLayout() {
                    return Array.isArray(this.layoutElements) && this.layoutElements.length > 0;
                },
                get sortedLayoutElements() {
                    if (!Array.isArray(this.layoutElements)) {
                        return [];
                    }
                    return [...this.layoutElements].sort((a, b) => (Number(a.z_index) || 0) - (Number(b.z_index) || 0));
                },
                sectionColor(sectionId) {
                    return this.sectionPaletteEntry(sectionId).border;
                },
                sectionPaletteEntry(sectionId) {
                    const sid = parseInt(sectionId, 10) || 0;
                    const row = (this.sectionsWithSeats || []).find(s => Number(s.id) === sid);
                    if (row && row.palette && row.palette.fill) {
                        return { bg: row.palette.fill, border: row.palette.stroke, text: row.palette.text };
                    }
                    const palette = [
                        { bg: '#2563eb', border: '#1e40af', text: '#ffffff' },
                        { bg: '#9333ea', border: '#6b21a8', text: '#ffffff' },
                        { bg: '#d97706', border: '#b45309', text: '#fffbeb' },
                        { bg: '#0891b2', border: '#0e7490', text: '#ffffff' },
                        { bg: '#059669', border: '#047857', text: '#ffffff' },
                        { bg: '#65a30d', border: '#4d7c0f', text: '#fffbeb' },
                        { bg: '#db2777', border: '#9d174d', text: '#ffffff' },
                    ];
                    return palette[Math.abs(sid) % palette.length];
                },
                formatSectionOptionLabel(s) {
                    const name = (s && s.name) ? s.name : ('Sección ' + (s && s.id));
                    const p = s && s.price;
                    if (p == null || p === '' || Number(p) <= 0) {
                        return name;
                    }
                    return name + ' — ' + Number(p).toFixed(0) + ' Bs';
                },
                layoutElType(el) {
                    if (!el) {
                        return '';
                    }
                    const raw = el.type;
                    if (raw != null && String(raw).trim() !== '') {
                        return String(raw).toLowerCase().trim();
                    }
                    if (el.seat_id) {
                        return 'seat';
                    }
                    return '';
                },
                layoutElementWrapperStyle(el) {
                    if (!el) {
                        return '';
                    }
                    const b = this.getLayoutContentBounds();
                    const pad = window.LAYOUT_MAP_CONTENT_PAD;
                    const x = Number(el.x) || 0;
                    const y = Number(el.y) || 0;
                    const w = Math.max(8, Number(el.w) || 48);
                    const h = Math.max(8, Number(el.h) || 48);
                    const ox = b ? (x - b.minX + pad) : x;
                    const oy = b ? (y - b.minY + pad) : y;
                    const rot = Number(el.rotation) || 0;
                    const z = Number(el.z_index) || 0;
                    return `left:${ox}px;top:${oy}px;width:${w}px;height:${h}px;transform:rotate(${rot}deg);z-index:${z};`;
                },
                seatSectionIdFromLayout(el) {
                    if (!el || !el.seat) return 0;
                    const sid = el.seat.section_id;
                    return sid != null ? parseInt(sid, 10) || 0 : 0;
                },
                layoutStageSpeakerFaceStyle(el) {
                    const dim = this.selectedSeatSectionId !== 0;
                    return dim ? 'opacity:0.42;' : 'opacity:1;';
                },
                layoutSeatFaceStyle(el) {
                    if (!el || this.layoutElType(el) !== 'seat') {
                        return '';
                    }
                    if (!el.seat) {
                        return 'opacity:1;background-color:#15803d;border-color:#14532d;color:#ffffff;box-shadow:0 1px 2px rgba(0,0,0,0.35);';
                    }
                    const id = parseInt(el.seat.id, 10);
                    const selected = this.selectedSeatIds.includes(id);
                    const seatSectionId = this.seatSectionIdFromLayout(el);
                    const passFilter = (this.selectedSeatSectionId === 0) || (seatSectionId === this.selectedSeatSectionId);
                    const available = sectionSeatAvailableIds.includes(id) && !el.seat.blocked;
                    const dimmed = this.selectedSeatSectionId !== 0 && !passFilter;
                    const opacity = dimmed ? 0.4 : 1;
                    let shadow = '0 1px 2px rgba(0,0,0,0.35)';
                    if (selected) {
                        shadow = '0 0 0 2px #fff, 0 0 0 5px rgba(229,9,20,0.95)';
                    }
                    if (selected) {
                        return `opacity:1;background-color:#e50914;border-color:#fecaca;color:#ffffff;box-shadow:${shadow};`;
                    }
                    if (!passFilter) {
                        const pal = seatSectionId ? this.sectionPaletteEntry(seatSectionId) : { bg: '#334155', border: '#475569', text: '#cbd5e1' };
                        return `opacity:${opacity};background-color:${pal.bg};border-color:${pal.border};color:${pal.text};box-shadow:none;`;
                    }
                    if (!available) {
                        return `opacity:${Math.min(1, opacity * 0.95)};background-color:#1e293b;border-color:#334155;color:#64748b;box-shadow:none;`;
                    }
                    if (seatSectionId) {
                        const pal = this.sectionPaletteEntry(seatSectionId);
                        return `opacity:1;background-color:${pal.bg};border-color:${pal.border};color:${pal.text};box-shadow:${shadow};`;
                    }
                    return `opacity:1;background-color:#15803d;border-color:#14532d;color:#ffffff;box-shadow:${shadow};`;
                },
                canSelectLayoutSeat(el) {
                    if (!el || !el.seat) return false;
                    const id = parseInt(el.seat.id, 10);
                    const selected = this.selectedSeatIds.includes(id);
                    const seatSectionId = this.seatSectionIdFromLayout(el);
                    const passFilter = (this.selectedSeatSectionId === 0) || (seatSectionId === this.selectedSeatSectionId);
                    const available = passFilter && sectionSeatAvailableIds.includes(id) && !el.seat.blocked;
                    return selected || (available && this.selectedSeatIds.length < maxSeats);
                },
                layoutSeatClass(el) {
                    if (!el || !el.seat) {
                        return 'border-slate-600 cursor-not-allowed';
                    }
                    const id = parseInt(el.seat.id, 10);
                    const selected = this.selectedSeatIds.includes(id);
                    const seatSectionId = this.seatSectionIdFromLayout(el);
                    const passFilter = (this.selectedSeatSectionId === 0) || (seatSectionId === this.selectedSeatSectionId);
                    const available = passFilter && sectionSeatAvailableIds.includes(id) && !el.seat.blocked;
                    if (!passFilter) {
                        return 'cursor-default';
                    }
                    if (selected) {
                        return 'cursor-pointer';
                    }
                    if (available) {
                        return 'cursor-pointer hover:brightness-110';
                    }
                    return 'cursor-not-allowed';
                },
                toggleLayoutSeat(el) {
                    if (!el || !el.seat) return;
                    const id = parseInt(el.seat.id, 10);
                    const idx = this.selectedSeatIds.indexOf(id);
                    if (idx >= 0) {
                        this.selectedSeatIds.splice(idx, 1);
                        return;
                    }
                    if (!this.canSelectLayoutSeat(el)) return;
                    this.selectedSeatIds.push(id);
                    this.selectedSeatIds.sort((a, b) => a - b);
                },
                get totalTickets() {
                    const seatCount = this.selectedSeatIds.length;
                    let sectionCount = 0;
                    for (const k in this.sectionQuantities) sectionCount += parseInt(this.sectionQuantities[k], 10) || 0;
                    return seatCount + sectionCount;
                },
                get ticketItems() {
                    const items = [];
                    this.selectedSeatIds.forEach(id => {
                        items.push({ type: 'seat', label: 'Butaca ' + (seatsMap[id] || id) });
                    });
                    sectionIdsWithoutSeats.forEach(sid => {
                        const qty = parseInt(this.sectionQuantities[sid], 10) || 0;
                        const name = sectionIdToName[sid] || ('Sección ' + sid);
                        for (let k = 0; k < qty; k++) items.push({ type: 'section', label: 'Sección ' + name });
                    });
                    return items;
                },
                get totalCost() {
                    let sum = 0;
                    this.selectedSeatIds.forEach(id => {
                        const p = seatIdToPrice[id];
                        if (p != null && p > 0) sum += parseFloat(p);
                    });
                    sectionIdsWithoutSeats.forEach(sid => {
                        const qty = parseInt(this.sectionQuantities[sid], 10) || 0;
                        const p = sectionIdToPrice[sid];
                        if (p != null && p > 0 && qty > 0) sum += parseFloat(p) * qty;
                    });
                    return Math.round(sum * 100) / 100;
                },
                get nameRange() {
                    const n = Math.min(maxSeats, this.totalTickets);
                    return Array.from({ length: n }, (_, i) => i + 1);
                },
                sectionSeatClass(seatId, availableIds) {
                    const id = parseInt(seatId, 10);
                    const selected = this.selectedSeatIds.includes(id);
                    const available = (availableIds || []).includes(id);
                    if (selected) return 'bg-[#e50914] text-white ring-2 ring-white';
                    if (available) return 'bg-emerald-600 hover:bg-emerald-500 text-white';
                    return 'bg-slate-700 text-slate-500 cursor-not-allowed';
                },
                canSelectSectionSeat(seatId, availableIds) {
                    const id = parseInt(seatId, 10);
                    const selected = this.selectedSeatIds.includes(id);
                    const available = (availableIds || []).includes(id);
                    return selected || (available && this.selectedSeatIds.length < maxSeats);
                },
                toggleSeat(seatId, availableIds) {
                    const id = parseInt(seatId, 10);
                    const idx = this.selectedSeatIds.indexOf(id);
                    if (idx >= 0) this.selectedSeatIds.splice(idx, 1);
                    else {
                        const available = (availableIds || []).includes(id);
                        if (available && this.selectedSeatIds.length < maxSeats) this.selectedSeatIds.push(id);
                    }
                    this.selectedSeatIds.sort((a,b)=>a-b);
                }
            };
        }
    </script>
@elseif(empty($seats))
    {{-- Sin venue: reserva por cantidad (legacy) --}}
    <div class="max-w-2xl mx-auto px-1" x-data="{ quantity: {{ old('quantity', 1) }}, singleName: {{ old('single_name', true) ? 'true' : 'false' }} }">
        <h1 class="font-display text-2xl sm:text-3xl font-bold text-[#e50914] tracking-widest mb-2">CHECKOUT — PASO 1</h1>
        <p class="text-lg sm:text-xl text-white/80 mb-2">{{ $event->name }}</p>
        <p class="text-white/60 text-sm mb-2">Cantidad y nombres para los tickets.</p>
        <p class="text-amber-200/90 text-sm mb-6 sm:mb-8">Este evento no tiene selección de butacas; solo elige la cantidad. En el paso 2 verás el resumen y subirás el comprobante.</p>

        <form method="POST" action="{{ route('reservations.store') }}" class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-6 sm:p-8 md:p-10 space-y-6">
            @csrf
            <input type="hidden" name="event_id" value="{{ $event->id }}">
            @error('event_id')<p class="text-sm text-red-400">{{ $message }}</p>@enderror

            <div>
                <label class="block text-sm font-medium text-white/80 mb-2">Cantidad de tickets</label>
                <select name="quantity" x-model.number="quantity" required
                        class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white focus:ring-2 focus:ring-[#e50914] focus:border-[#e50914]">
                    @for($i = 1; $i <= 4; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <p class="block text-sm font-medium text-white/80 mb-2">Nombres en los tickets</p>
                <div class="flex flex-col gap-3 sm:flex-row sm:gap-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="single_name" value="1" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                        <span class="text-white/70">Un nombre para todos</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="single_name" value="0" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                        <span class="text-white/70">Un nombre por ticket</span>
                    </label>
                </div>
            </div>

            <div x-show="singleName === '1' || singleName === true">
                <label for="holder_name" class="block text-sm font-medium text-white/80 mb-1">Nombre para todos los tickets</label>
                <input id="holder_name" type="text" name="holder_name" value="{{ old('holder_name') }}" maxlength="255"
                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914] @error('holder_name') border-red-500 @enderror">
                @error('holder_name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <div x-show="singleName === '0' || singleName === false" x-cloak>
                @for($i = 1; $i <= 4; $i++)
                    <div class="mb-4" x-show="quantity >= {{ $i }}">
                        <label for="holder_name_{{ $i }}" class="block text-sm font-medium text-white/80 mb-1">Nombre ticket {{ $i }}</label>
                        <input id="holder_name_{{ $i }}" type="text" name="holder_name_{{ $i }}" value="{{ old("holder_name_{$i}") }}" maxlength="255"
                               class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914]">
                        @error("holder_name_{$i}")<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endfor
            </div>

            @if(config('services.recaptcha.site_key'))
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            @endif
            @error('g-recaptcha-response')
                <p class="text-sm text-red-400">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full rounded-xl bg-[#e50914] px-5 py-4 text-white font-bold hover:bg-red-600 transition">
                Continuar al paso 2 — Comprobante de pago
            </button>
        </form>
    </div>
@else
    {{-- Con venue: mapa de butacas --}}
    @php
        $seatsByRow = $seats->groupBy('row');
        $maxCols = $seatsByRow->isEmpty() ? 1 : $seatsByRow->max(fn ($r) => $r->count());
        $maxSeats = \App\Services\ReservationService::MAX_SEATS;
        $oldSeatIds = array_map('intval', (array) old('seat_ids', []));
        $oldNames = [];
        $oldSeatFor = [];
        foreach (range(1, count($oldSeatIds) ?: 1) as $i) {
            $oldNames[$i] = old("holder_name_{$i}", '');
            $oldSeatFor[$i] = old("seat_for_{$i}");
        }
        $seatFor = [];
        for ($i = 1; $i <= count($oldSeatIds); $i++) {
            $seatFor[$i] = isset($oldSeatFor[$i]) ? (int) $oldSeatFor[$i] : ($oldSeatIds[$i - 1] ?? null);
        }
        $layoutElementsData = $layoutElements ?? [];
        $layoutCanvasSimple = $layoutCanvas ?? ['width' => null, 'height' => null];
    @endphp
    @php
        $alpineDataJson = json_encode([
            'selectedIds' => $oldSeatIds,
            'singleName' => old('single_name', true),
            'availableIds' => $availableSeatIds,
            'maxSeats' => $maxSeats,
            'oldNames' => $oldNames,
            'oldSeatFor' => $oldSeatFor,
            'seatsMap' => $seatsMap ?? [],
            'seatFor' => $seatFor,
            'layoutElements' => $layoutElementsData,
            'layoutCanvas' => $layoutCanvasSimple,
            'sectionPalettesById' => $sectionPalettesById ?? [],
        ], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    @endphp
    <div class="mx-auto w-full min-w-0 max-w-full px-2 sm:max-w-4xl sm:px-0"
         x-data="{
            ...({{ $alpineDataJson }}),
            _layoutViewportUserScale: null,
            _layoutCheckoutWheelBound: false,
            _layoutOrientationHandler: null,
            isAvailable(id) { return this.availableIds.includes(parseInt(id, 10)); },
            isSelected(id) { return this.selectedIds.includes(parseInt(id, 10)); },
            isBlocked(seat) { return !!seat.blocked; },
            canSelect(seat) { return !this.isBlocked(seat) && this.isAvailable(seat.id) && (this.isSelected(seat.id) || this.selectedIds.length < this.maxSeats); },
            toggle(seat) {
                if (!this.canSelect(seat)) return;
                const id = parseInt(seat.id, 10);
                const idx = this.selectedIds.indexOf(id);
                if (idx >= 0) { this.selectedIds.splice(idx, 1); }
                else { this.selectedIds.push(id); this.selectedIds.sort((a,b)=>a-b); }
            },
            onSeatAssign(ticketNum, newVal) {
                newVal = parseInt(newVal, 10);
                const oldVal = this.seatFor[ticketNum];
                const otherKey = Object.keys(this.seatFor).find(k => parseInt(k, 10) !== ticketNum && this.seatFor[k] === newVal);
                if (otherKey) this.seatFor[otherKey] = oldVal;
                this.seatFor[ticketNum] = newVal;
            },
            hasCustomLayout() { return Array.isArray(this.layoutElements) && this.layoutElements.length > 0; },
            _layoutViewportScale: 1,
            _layoutViewportRo: null,
            init() {
                if (!Array.isArray(this.selectedIds)) this.selectedIds = [];
                if (typeof this.seatFor !== 'object') this.seatFor = {};
                const syncSeatFor = () => {
                    const ids = this.selectedIds || [];
                    for (let i = 0; i < ids.length; i++) {
                        const j = i + 1;
                        if (this.seatFor[j] === undefined || !ids.includes(this.seatFor[j])) this.seatFor[j] = ids[i];
                    }
                };
                syncSeatFor();
                this.$watch('selectedIds', syncSeatFor, { deep: true });
                this.$nextTick(() => this.setupLayoutViewportObserverSimple());
            },
            setupLayoutViewportObserverSimple() {
                const el = this.$refs.layoutViewport;
                if (!el || this._layoutViewportRo) return;
                const self = this;
                this._layoutViewportRo = new ResizeObserver(() => this.recalcLayoutViewportScaleSimple());
                this._layoutViewportRo.observe(el);
                this.recalcLayoutViewportScaleSimple();
                if (!this._layoutCheckoutWheelBound) {
                    this._layoutCheckoutWheelBound = true;
                    el.addEventListener('wheel', function(e) {
                        if (!(e.ctrlKey || e.metaKey)) return;
                        e.preventDefault();
                        if (e.deltaY > 0) self.layoutZoomOutSimple(); else self.layoutZoomInSimple();
                    }, { passive: false });
                    this._layoutOrientationHandler = function() {
                        setTimeout(function() { self.recalcLayoutViewportScaleSimple(); }, 200);
                    };
                    window.addEventListener('orientationchange', this._layoutOrientationHandler);
                }
            },
            recalcLayoutViewportScaleSimple() {
                const el = this.$refs.layoutViewport;
                if (!el) return;
                const pad = window.LAYOUT_CHECKOUT_ZOOM_PAD;
                const dw = this.layoutDesignWidthSimple;
                const dh = this.layoutDesignHeightSimple;
                const fit = window.layoutCheckoutFitScale(el, dw, dh, pad);
                const u = this._layoutViewportUserScale;
                if (u == null || !Number.isFinite(Number(u))) {
                    this._layoutViewportScale = fit;
                } else {
                    this._layoutViewportScale = Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, Math.min(Number(u), 1));
                }
            },
            layoutZoomPercentSimple() {
                return Math.round((Number(this._layoutViewportScale) || 1) * 100);
            },
            layoutZoomOutSimple() {
                const cur = this._layoutViewportUserScale != null ? Number(this._layoutViewportUserScale) : Number(this._layoutViewportScale);
                this._layoutViewportUserScale = Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, cur / 1.2);
                this.recalcLayoutViewportScaleSimple();
            },
            layoutZoomInSimple() {
                const cur = this._layoutViewportUserScale != null ? Number(this._layoutViewportUserScale) : Number(this._layoutViewportScale);
                this._layoutViewportUserScale = Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, Math.min(1, cur * 1.2));
                this.recalcLayoutViewportScaleSimple();
            },
            layoutZoomResetFitSimple() {
                this._layoutViewportUserScale = null;
                const vp = this.$refs.layoutViewport;
                if (vp) {
                    vp.scrollLeft = 0;
                    vp.scrollTop = 0;
                }
                this.recalcLayoutViewportScaleSimple();
            },
            inferLayoutCanvasWidthSimple() {
                const els = this.layoutElements;
                if (!Array.isArray(els) || !els.length) return 960;
                let m = 320;
                for (let i = 0; i < els.length; i++) {
                    const el = els[i];
                    const r = (Number(el.x) || 0) + Math.max(8, Number(el.w) || 48) + 48;
                    if (r > m) m = r;
                }
                return Math.ceil(m);
            },
            inferLayoutCanvasHeightSimple() {
                const els = this.layoutElements;
                if (!Array.isArray(els) || !els.length) return 640;
                let m = 420;
                for (let i = 0; i < els.length; i++) {
                    const el = els[i];
                    const r = (Number(el.y) || 0) + Math.max(8, Number(el.h) || 48) + 48;
                    if (r > m) m = r;
                }
                return Math.ceil(m);
            },
            get layoutDesignWidthSimple() {
                const b = window.computeLayoutContentBoundsFromElements(this.layoutElements);
                if (b) {
                    return Math.max(200, Math.ceil(b.maxX - b.minX + 2 * window.LAYOUT_MAP_CONTENT_PAD));
                }
                const lc = this.layoutCanvas || {};
                const w = lc.width != null ? Number(lc.width) : null;
                if (w != null && w > 0) return w;
                return this.inferLayoutCanvasWidthSimple();
            },
            get layoutDesignHeightSimple() {
                const b = window.computeLayoutContentBoundsFromElements(this.layoutElements);
                if (b) {
                    return Math.max(200, Math.ceil(b.maxY - b.minY + 2 * window.LAYOUT_MAP_CONTENT_PAD));
                }
                const lc = this.layoutCanvas || {};
                const h = lc.height != null ? Number(lc.height) : null;
                if (h != null && h > 0) return h;
                return this.inferLayoutCanvasHeightSimple();
            },
            get layoutScaledHostStyleSimple() {
                const s = this._layoutViewportScale;
                const dw = this.layoutDesignWidthSimple;
                const dh = this.layoutDesignHeightSimple;
                return 'width:' + (dw * s) + 'px;height:' + (dh * s) + 'px;';
            },
            get layoutScaledStageStyleSimple() {
                const s = this._layoutViewportScale;
                const dw = this.layoutDesignWidthSimple;
                const dh = this.layoutDesignHeightSimple;
                return 'position:absolute;left:0;top:0;width:' + dw + 'px;height:' + dh + 'px;transform:scale(' + s + ');transform-origin:top left;';
            },
            get sortedLayoutElements() {
                if (!Array.isArray(this.layoutElements)) return [];
                return [...this.layoutElements].sort((a, b) => (Number(a.z_index) || 0) - (Number(b.z_index) || 0));
            },
            findSeatById(id) {
                const sid = parseInt(id, 10);
                return this.layoutElements.find(el => this.layoutElType(el) === 'seat' && el.seat_id === sid && el.seat) || null;
            },
            layoutElType(el) {
                if (!el) return '';
                const raw = el.type;
                if (raw != null && String(raw).trim() !== '') return String(raw).toLowerCase().trim();
                if (el.seat_id) return 'seat';
                return '';
            },
            layoutElementWrapperStyle(el) {
                if (!el) return '';
                const b = window.computeLayoutContentBoundsFromElements(this.layoutElements);
                const pad = window.LAYOUT_MAP_CONTENT_PAD;
                const x = Number(el.x) || 0;
                const y = Number(el.y) || 0;
                const w = Math.max(8, Number(el.w) || 48);
                const h = Math.max(8, Number(el.h) || 48);
                const ox = b ? (x - b.minX + pad) : x;
                const oy = b ? (y - b.minY + pad) : y;
                const rot = Number(el.rotation) || 0;
                const z = Number(el.z_index) || 0;
                return `left:${ox}px;top:${oy}px;width:${w}px;height:${h}px;transform:rotate(${rot}deg);z-index:${z};`;
            },
            sectionPaletteEntrySimple(sectionId) {
                const sid = parseInt(sectionId, 10) || 0;
                const pal = this.sectionPalettesById && this.sectionPalettesById[sid];
                if (pal && pal.fill) {
                    return { bg: pal.fill, border: pal.stroke, text: pal.text };
                }
                const palette = [
                    { bg: '#2563eb', border: '#1e40af', text: '#ffffff' },
                    { bg: '#9333ea', border: '#6b21a8', text: '#ffffff' },
                    { bg: '#d97706', border: '#b45309', text: '#fffbeb' },
                    { bg: '#0891b2', border: '#0e7490', text: '#ffffff' },
                    { bg: '#059669', border: '#047857', text: '#ffffff' },
                    { bg: '#65a30d', border: '#4d7c0f', text: '#fffbeb' },
                    { bg: '#db2777', border: '#9d174d', text: '#ffffff' },
                ];
                return palette[Math.abs(sid) % palette.length];
            },
            seatSectionIdFromLayoutSimple(el) {
                if (!el || !el.seat) return 0;
                const sid = el.seat.section_id;
                return sid != null ? parseInt(sid, 10) || 0 : 0;
            },
            layoutSeatFaceStyleSimple(el) {
                if (!el || this.layoutElType(el) !== 'seat') return '';
                if (!el.seat) {
                    return 'background-color:#15803d;border-color:#14532d;color:#fff;box-shadow:0 1px 2px rgba(0,0,0,0.35);';
                }
                const sid = parseInt(el.seat.id, 10);
                const selected = this.isSelected(sid);
                const can = this.canSelect({ id: sid, blocked: !!el.seat.blocked });
                let shadow = '0 1px 2px rgba(0,0,0,0.35)';
                if (selected) shadow = '0 0 0 2px #fff, 0 0 0 5px rgba(229,9,20,0.95)';
                if (selected) {
                    return `background-color:#e50914;border-color:#fecaca;color:#fff;box-shadow:${shadow};`;
                }
                if (!can) {
                    return `background-color:#1e293b;border-color:#334155;color:#64748b;box-shadow:none;`;
                }
                const sec = this.seatSectionIdFromLayoutSimple(el);
                if (sec) {
                    const pal = this.sectionPaletteEntrySimple(sec);
                    return `background-color:${pal.bg};border-color:${pal.border};color:${pal.text};box-shadow:${shadow};`;
                }
                return `background-color:#15803d;border-color:#14532d;color:#fff;box-shadow:${shadow};`;
            },
            layoutSeatClass(el) {
                if (!el || !el.seat) return 'border-slate-600 cursor-not-allowed';
                const sid = parseInt(el.seat.id, 10);
                if (this.isSelected(sid)) return 'cursor-pointer ring-2 ring-white';
                if (!this.canSelect({ id: sid, blocked: !!el.seat.blocked })) return 'cursor-not-allowed';
                return 'cursor-pointer hover:brightness-110';
            },
            toggleLayoutSeat(el) {
                if (!el || !el.seat) return;
                this.toggle({ id: parseInt(el.seat.id, 10), blocked: !!el.seat.blocked });
            }
         }">
        <h1 class="font-display text-2xl sm:text-3xl font-bold text-[#e50914] tracking-widest mb-2">CHECKOUT — PASO 1</h1>
        <p class="text-lg sm:text-xl text-white/80 mb-2">{{ $event->name }}</p>
        <p class="text-white/60 text-sm mb-4 sm:mb-6">Elige tus butacas haciendo clic (máximo {{ $maxSeats }}). Luego los nombres. Al continuar pasarás al paso 2 para subir el comprobante.</p>

        <form method="POST" action="{{ route('reservations.store') }}" class="space-y-6"
              @submit="const t = $el.querySelector('[name=seat_ids_csv]'); if (t && Array.isArray(selectedIds)) t.value = selectedIds.join(',');">
            @csrf
            <input type="hidden" name="event_id" value="{{ $event->id }}">

            @if($errors->any())
                <div class="rounded-xl border-2 border-red-500 bg-red-900/30 p-4 text-red-200">
                    <p class="font-semibold mb-2">Revisa los datos:</p>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <template x-if="hasCustomLayout()">
                <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-5 sm:p-6">
                    <p class="text-white/70 text-sm mb-3 text-center">Plano del venue</p>
                    <div class="mb-3 flex flex-wrap items-center justify-center gap-2 rounded-lg border border-red-900/50 bg-black/50 px-2 py-2 text-white shadow-inner">
                        <span class="w-11 text-center font-mono text-xs tabular-nums text-white/90" x-text="layoutZoomPercentSimple() + '%'"></span>
                        <button type="button" class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-2 text-lg font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomOutSimple()" title="Alejar plano" aria-label="Alejar plano">−</button>
                        <button type="button" class="inline-flex h-9 items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-3 text-xs font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomResetFitSimple()" title="Encajar al espacio" aria-label="Encajar plano">Encajar</button>
                        <button type="button" class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-2 text-lg font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomInSimple()" title="Acercar plano" aria-label="Acercar plano">+</button>
                        <span class="hidden text-[10px] text-white/45 sm:inline">Ctrl + rueda</span>
                    </div>
                    <div x-ref="layoutViewport"
                         @resize.window="recalcLayoutViewportScaleSimple()"
                         class="relative w-full min-h-[200px] max-h-[min(78dvh,900px)] touch-manipulation overflow-auto overscroll-contain rounded-xl border border-red-900/40 bg-[radial-gradient(circle,_rgba(255,255,255,0.12)_1px,_transparent_1px)] bg-[size:16px_16px] p-2 sm:max-h-[min(86vh,90dvh)]">
                        <div class="relative mx-auto shrink-0" :style="layoutScaledHostStyleSimple">
                            <div class="relative" :style="layoutScaledStageStyleSimple">
                                <template x-for="el in sortedLayoutElements" :key="el.id">
                                    <div class="absolute isolate" :style="layoutElementWrapperStyle(el) + 'pointer-events:none;'">
                                        <template x-if="layoutElType(el) === 'seat'">
                                            <button type="button"
                                                    class="absolute inset-0 z-10 rounded-md text-[9px] sm:text-[10px] font-bold px-0.5 sm:px-1 transition border-2 flex items-center justify-center leading-none overflow-hidden text-center pointer-events-auto"
                                                    :class="layoutSeatClass(el)"
                                                    :style="layoutSeatFaceStyleSimple(el)"
                                                    :disabled="!canSelect({ id: parseInt(el.seat.id, 10), blocked: !!el.seat.blocked }) && !isSelected(parseInt(el.seat.id, 10))"
                                                    @click="toggleLayoutSeat(el)"
                                                    :title="`Butaca ${el.seat.label}`">
                                                <span class="truncate max-w-full" x-text="el.seat.label"></span>
                                            </button>
                                        </template>
                                        <template x-if="layoutElType(el) === 'stage'">
                                            <div class="absolute inset-0 z-0 flex items-center justify-center rounded-md border border-red-500/40 bg-red-700 px-0.5 text-white shadow-md pointer-events-none overflow-hidden">
                                                <span class="max-h-full overflow-hidden text-center text-[8px] font-semibold uppercase leading-tight sm:text-[10px]" x-text="(el.meta && el.meta.label) ? el.meta.label : 'ESCENARIO'"></span>
                                            </div>
                                        </template>
                                        <template x-if="layoutElType(el) === 'speaker'">
                                            <div class="absolute inset-0 z-0 flex items-center justify-center rounded-md border border-amber-400/40 bg-amber-600 px-0.5 text-white shadow-md pointer-events-none overflow-hidden">
                                                <span class="max-h-full overflow-hidden text-center text-[8px] font-semibold uppercase leading-tight sm:text-[10px]" x-text="(el.meta && el.meta.label) ? el.meta.label : 'PARLANTE'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-5 sm:p-6" x-show="!hasCustomLayout()">
                {{-- Plano escalado al viewport: todas las butacas visibles, proporción correcta --}}
                @php
                    $labelW = 2.5;
                    $gapLabel = 0.75;
                    $gapSeat = 0.5;
                    $paddingVw = 5;
                @endphp
                {{-- Tamaño de butaca proporcional al dispositivo: mínimo 0.875rem en móvil, máximo 3rem en desktop --}}
                <div class="w-full seat-plan-grid overflow-hidden" style="--cols: {{ $maxCols }}; --seat-size: clamp(0.875rem, calc((100vw - {{ $paddingVw }}rem - {{ $labelW }}rem - {{ $gapLabel }}rem - (var(--cols) - 1) * {{ $gapSeat }}rem) / var(--cols)), 3rem);">
                    <div class="flex flex-col gap-3 items-center">
                    {{-- Escenario: misma fila — PARLANTE (círculo) en A1 y A7, línea roja + ESCENARIO centrada entre ambos --}}
                    <div class="flex gap-3 items-end flex-nowrap mb-1">
                        <span class="shrink-0 invisible" style="width: var(--seat-size); height: var(--seat-size);" aria-hidden="true"></span>
                        <div class="flex items-end gap-2 shrink-0 min-w-0" style="width: calc(var(--seat-size) * {{ $maxCols }} + ({{ $maxCols }} - 1) * 0.5rem);">
                            <div class="flex flex-col items-center justify-center rounded-lg border border-amber-600/50 bg-amber-900/20 shrink-0 py-1 px-1.5 gap-0.5" style="width: var(--seat-size); min-height: var(--seat-size);" role="img" aria-label="Parlante">
                                <span class="text-[9px] sm:text-[10px] font-semibold text-amber-400/90 uppercase leading-tight"><span class="sm:hidden">P</span><span class="hidden sm:inline">PARLANTE</span></span>
                            </div>
                            <div class="flex-1 flex flex-col items-center justify-end gap-0.5 min-w-0 pb-0.5">
                                <div class="w-full rounded-sm bg-red-700 min-h-[3px]" style="height: 3px;" role="img" aria-label="Línea de escenario"></div>
                                <span class="text-[10px] sm:text-xs font-medium text-red-400 uppercase tracking-wider">ESCENARIO</span>
                            </div>
                            <div class="flex flex-col items-center justify-center rounded-lg border border-amber-600/50 bg-amber-900/20 shrink-0 py-1 px-1.5 gap-0.5" style="width: var(--seat-size); min-height: var(--seat-size);" role="img" aria-label="Parlante">
                                <span class="text-[9px] sm:text-[10px] font-semibold text-amber-400/90 uppercase leading-tight"><span class="sm:hidden">P</span><span class="hidden sm:inline">PARLANTE</span></span>
                            </div>
                        </div>
                    </div>
                    @foreach($seatsByRow as $row => $rowSeats)
                        @php $rowLetter = $rowSeats->first()->row_letter ?? chr(64 + (int)$row); @endphp
                        <div class="flex gap-3 items-center justify-center flex-nowrap">
                            <span class="seat-plan-label flex shrink-0 items-center justify-center rounded-lg border border-red-900/50 bg-black/40 font-bold text-[#e50914]" style="width: var(--seat-size); height: var(--seat-size); font-size: min(0.875rem, var(--seat-size)); line-height: 1;" aria-label="Fila {{ $rowLetter }}">{{ $rowLetter }}</span>
                            <div class="flex gap-2 justify-center flex-nowrap shrink-0">
                                @foreach($rowSeats as $seat)
                                    <button type="button"
                                            @click="toggle({{ json_encode($seat) }})"
                                            :disabled="!canSelect({{ json_encode($seat) }})"
                                            :class="{
                                                'bg-emerald-600 hover:bg-emerald-500 text-white': isAvailable({{ $seat->id }}) && !isBlocked({{ json_encode($seat) }}) && !isSelected({{ $seat->id }}),
                                                'bg-[#e50914] text-white ring-2 ring-white': isSelected({{ $seat->id }}),
                                                'bg-slate-700 text-slate-500 cursor-not-allowed': isBlocked({{ json_encode($seat) }}) || (!isAvailable({{ $seat->id }}) && !isSelected({{ $seat->id }}))
                                            }"
                                            class="seat-plan-cell rounded-lg font-mono font-bold transition shrink-0 disabled:opacity-70 flex items-center justify-center"
                                            style="width: var(--seat-size); height: var(--seat-size); min-width: var(--seat-size); font-size: min(0.875rem, var(--seat-size)); line-height: 1;"
                                            :title="'Fila {{ $seat->row_letter }} Butaca {{ $seat->number }}' + (isBlocked({{ json_encode($seat) }}) ? ' (bloqueada)' : (!isAvailable({{ $seat->id }}) ? ' (reservada)' : ''))">
                                        {{ $seat->number }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>

                {{-- Envío de seat_ids: inputs ocultos + copia en textarea por si Alpine no incluye los inputs en el submit --}}
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" :name="'seat_ids[]'" :value="id">
                </template>
                <textarea name="seat_ids_csv" :value="Array.isArray(selectedIds) ? selectedIds.join(',') : ''" class="hidden" aria-hidden="true" tabindex="-1"></textarea>
            </div>

            <p class="text-white/80 text-sm" x-show="selectedIds.length > 0">
                Has elegido <strong x-text="selectedIds.length"></strong> butaca(s). <span x-text="selectedIds.length >= maxSeats ? '(máximo alcanzado)' : ''"></span>
            </p>
            @error('seat_ids')<p class="text-sm text-red-400">{{ $message }}</p>@enderror

            <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-5 sm:p-6 space-y-4">
                <p class="block text-sm font-medium text-white/80">Nombres en los tickets</p>
                <div class="flex flex-col gap-3 sm:flex-row sm:gap-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="single_name" value="1" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                        <span class="text-white/70">Un nombre para todos</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="single_name" value="0" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                        <span class="text-white/70">Un nombre por ticket</span>
                    </label>
                </div>

                <div x-show="singleName === '1' || singleName === true">
                    <label for="holder_name" class="block text-sm font-medium text-white/80 mb-1">Nombre para todos los tickets</label>
                    <input id="holder_name" type="text" name="holder_name" value="{{ old('holder_name') }}" maxlength="255"
                           class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914] @error('holder_name') border-red-500 @enderror">
                    @error('holder_name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                </div>

                <div x-show="singleName === '0' || singleName === false" x-cloak>
                    <p class="text-white/60 text-xs mb-3">Asigna un nombre y una butaca a cada ticket.</p>
                    <template x-for="(id, i) in selectedIds" :key="id">
                        <div class="mb-4 p-4 rounded-xl border border-red-900/30 bg-black/40 space-y-3">
                            <p class="text-sm font-medium text-[#e50914]" x-text="'Ticket ' + (i+1)"></p>
                            <div>
                                <label :for="'holder_name_'+ (i+1)" class="block text-sm font-medium text-white/80 mb-1">Nombre</label>
                                <input :id="'holder_name_'+ (i+1)" type="text" :name="'holder_name_' + (i+1)" maxlength="255"
                                       :value="oldNames[i+1] || ''"
                                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#e50914]"
                                       placeholder="Ej. Juan Pérez">
                            </div>
                            <div>
                                <label :for="'seat_for_'+ (i+1)" class="block text-sm font-medium text-white/80 mb-1">Butaca</label>
                                <select :id="'seat_for_'+ (i+1)" :name="'seat_for_' + (i+1)" required
                                        :value="seatFor[i+1]"
                                        @input="onSeatAssign(i+1, $event.target.value)"
                                        class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white focus:ring-2 focus:ring-[#e50914]">
                                    <template x-for="sid in selectedIds" :key="sid">
                                        <option :value="sid" x-text="seatsMap[sid] ? seatsMap[sid].label : sid"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            @if(config('services.recaptcha.site_key'))
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            @endif
            @error('g-recaptcha-response')
                <p class="text-sm text-red-400">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full rounded-xl bg-[#e50914] px-5 py-4 text-white font-bold hover:bg-red-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!Array.isArray(selectedIds) || selectedIds.length === 0">
                Continuar al paso 2 — Comprobante de pago
            </button>
        </form>
    </div>
@endif
@endsection
