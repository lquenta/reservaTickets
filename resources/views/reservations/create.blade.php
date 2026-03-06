@extends('layouts.app')

@section('title', 'Checkout - ' . $event->name)

@section('content')
{{-- Indicador de pasos del checkout --}}
<div class="max-w-2xl mx-auto mb-8">
    <div class="flex items-center justify-center gap-4">
        <div class="flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#e50914] text-sm font-bold text-white">1</span>
            <span class="text-sm font-medium text-[#e50914]">Elige butacas / datos</span>
        </div>
        <div class="h-px w-12 bg-red-900/50" aria-hidden="true"></div>
        <div class="flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-red-900/60 bg-black/40 text-sm font-medium text-white/60">2</span>
            <span class="text-sm text-white/50">Comprobante de pago</span>
        </div>
    </div>
</div>

@if(!empty($sectionsData))
    {{-- Con secciones: por sector con/sin butacas --}}
    @php
        $maxSeats = \App\Services\ReservationService::MAX_SEATS;
    @endphp
    @php
        $sectionIdsWithoutSeats = collect($sectionsData)->where('has_seats', false)->pluck('id')->values()->all();
        $seatsMapFlat = $seatsMap ?? [];
        $seatIdToPrice = $seatIdToPrice ?? [];
        $sectionIdToPrice = $sectionIdToPrice ?? [];
        $sectionIdToName = $sectionIdToName ?? [];
    @endphp
    <div class="max-w-4xl mx-auto" x-data="reservationSections({
        maxSeats: {{ $maxSeats }},
        sectionIdsWithoutSeats: {{ json_encode($sectionIdsWithoutSeats) }},
        seatsMap: {{ json_encode($seatsMapFlat) }},
        seatIdToPrice: {{ json_encode($seatIdToPrice) }},
        sectionIdToPrice: {{ json_encode($sectionIdToPrice) }},
        sectionIdToName: {{ json_encode($sectionIdToName) }},
        oldSeatIds: {{ json_encode(array_map('intval', (array) old('seat_ids', []))) }},
        oldSectionQuantities: {{ json_encode(old('section_quantities', [])) }},
        oldSingleName: {{ old('single_name', true) ? 'true' : 'false' }},
        oldNames: {{ json_encode(array_merge([0 => ''], array_map(fn ($i) => old("holder_name_{$i}", ''), range(1, $maxSeats)))) }}
    })">
        <h1 class="font-display text-3xl font-bold text-[#e50914] tracking-widest mb-2">CHECKOUT — PASO 1</h1>
        <p class="text-xl text-white/80 mb-2">{{ $event->name }}</p>
        <p class="text-white/60 text-sm mb-6">Elige butacas y/o entradas por sección. Máximo {{ $maxSeats }} entradas en total.</p>

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

            @foreach($sectionsData as $section)
                <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-6">
                    <h2 class="text-lg font-semibold text-[#e50914] mb-1">{{ $section['name'] }}</h2>
                    @if(isset($section['price']) && $section['price'] !== null && $section['price'] > 0)
                        <p class="text-white/60 text-sm mb-4">Precio: {{ number_format($section['price'], 2) }} Bs</p>
                    @endif
                    @if($section['has_seats'])
                        <p class="text-white/70 text-sm mb-3">Elige butacas (máx. {{ $maxSeats }} en total entre todas las secciones).</p>
                        @php
                            $seatsByRow = $section['seats']->groupBy('row');
                            $sectionAvailableIds = $section['availableSeatIds'];
                        @endphp
                        <div class="flex flex-col gap-2 items-center">
                            @foreach($seatsByRow as $row => $rowSeats)
                                @php $rowLetter = $rowSeats->first()->row_letter ?? chr(64 + (int)$row); @endphp
                                <div class="flex gap-2 items-center justify-center">
                                    <span class="w-8 text-center font-bold text-[#e50914] text-sm">{{ $rowLetter }}</span>
                                    @foreach($rowSeats as $seat)
                                        <button type="button"
                                                class="seat-btn rounded-lg w-10 h-10 flex items-center justify-center text-sm font-mono transition"
                                                :class="sectionSeatClass({{ $seat->id }}, {{ json_encode($section['availableSeatIds']) }})"
                                                data-seat-id="{{ $seat->id }}"
                                                data-section-id="{{ $section['id'] }}"
                                                @click="toggleSeat({{ $seat->id }})">
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

            <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-6">
                <p class="text-white/80 mb-2" x-show="totalTickets > 0">Total: <strong x-text="totalTickets"></strong> entrada(s).</p>
                <p class="text-white/80 mb-2" x-show="totalTickets > 0 && totalCost > 0">Costo total: <strong class="text-[#e50914]" x-text="'Bs ' + (typeof totalCost === 'number' ? totalCost.toFixed(2) : '0.00')"></strong></p>
                <template x-for="id in selectedSeatIds" :key="id">
                    <input type="hidden" name="seat_ids[]" :value="id">
                </template>
            </div>

            <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-6 space-y-4">
                <p class="block text-sm font-medium text-white/80">Nombres en los tickets</p>
                <label class="inline-flex items-center mr-6">
                    <input type="radio" name="single_name" value="1" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                    <span class="ml-2 text-white/70">Un nombre para todos</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="single_name" value="0" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                    <span class="ml-2 text-white/70">Un nombre por ticket</span>
                </label>
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
            const sectionQuantities = {};
            sectionIdsWithoutSeats.forEach(id => {
                sectionQuantities[id] = (config.oldSectionQuantities && (config.oldSectionQuantities[id] != null ? config.oldSectionQuantities[id] : config.oldSectionQuantities[String(id)])) || 0;
            });
            return {
                selectedSeatIds: Array.isArray(config.oldSeatIds) ? config.oldSeatIds : [],
                sectionQuantities: sectionQuantities,
                singleName: config.oldSingleName !== false,
                holderName: (config.oldNames && config.oldNames[1]) || '',
                holderNames: config.oldNames || {},
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
                toggleSeat(seatId) {
                    const id = parseInt(seatId, 10);
                    const idx = this.selectedSeatIds.indexOf(id);
                    if (idx >= 0) this.selectedSeatIds.splice(idx, 1);
                    else if (this.selectedSeatIds.length < maxSeats) this.selectedSeatIds.push(id);
                    this.selectedSeatIds.sort((a,b)=>a-b);
                }
            };
        }
    </script>
@elseif(empty($seats))
    {{-- Sin venue: reserva por cantidad (legacy) --}}
    <div class="max-w-2xl mx-auto" x-data="{ quantity: {{ old('quantity', 1) }}, singleName: {{ old('single_name', true) ? 'true' : 'false' }} }">
        <h1 class="font-display text-3xl font-bold text-[#e50914] tracking-widest mb-2">CHECKOUT — PASO 1</h1>
        <p class="text-xl text-white/80 mb-2">{{ $event->name }}</p>
        <p class="text-white/60 text-sm mb-2">Cantidad y nombres para los tickets.</p>
        <p class="text-amber-200/90 text-sm mb-8">Este evento no tiene selección de butacas; solo elige la cantidad. En el paso 2 verás el resumen y subirás el comprobante.</p>

        <form method="POST" action="{{ route('reservations.store') }}" class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-8 md:p-10 space-y-6">
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
                <label class="inline-flex items-center mr-6">
                    <input type="radio" name="single_name" value="1" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                    <span class="ml-2 text-white/70">Un nombre para todos</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="single_name" value="0" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                    <span class="ml-2 text-white/70">Un nombre por ticket</span>
                </label>
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
        ], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    @endphp
    <div class="max-w-4xl mx-auto"
         x-data="{
            ...({{ $alpineDataJson }}),
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
            }
         }"
         x-init="if (!Array.isArray(selectedIds)) selectedIds = [];">
        <h1 class="font-display text-3xl font-bold text-[#e50914] tracking-widest mb-2">CHECKOUT — PASO 1</h1>
        <p class="text-xl text-white/80 mb-2">{{ $event->name }}</p>
        <p class="text-white/60 text-sm mb-6">Elige tus butacas haciendo clic (máximo {{ $maxSeats }}). Luego los nombres. Al continuar pasarás al paso 2 para subir el comprobante.</p>

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

            <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-6">
                <p class="text-sm font-medium text-white/70 mb-3 text-center">Escenario</p>
                <div class="h-2 rounded bg-red-900/50 mb-8 mx-auto max-w-md"></div>

                {{-- Plano escalado al viewport: todas las butacas visibles, proporción correcta --}}
                @php
                    $labelW = 2.5;
                    $gapLabel = 0.75;
                    $gapSeat = 0.5;
                    $paddingVw = 5;
                @endphp
                <div class="w-full seat-plan-grid overflow-hidden" style="--cols: {{ $maxCols }}; --seat-size: min(2.5rem, max(1rem, calc((100vw - {{ $paddingVw }}rem - {{ $labelW }}rem - {{ $gapLabel }}rem - (var(--cols) - 1) * {{ $gapSeat }}rem) / var(--cols))));">
                    <div class="flex flex-col gap-3 items-center">
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
                                            title="Fila {{ $seat->row_letter }} Butaca {{ $seat->number }} {{ $seat->blocked ? '(bloqueada)' : '' }}">
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

            <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-6 space-y-4">
                <p class="block text-sm font-medium text-white/80">Nombres en los tickets</p>
                <label class="inline-flex items-center mr-6">
                    <input type="radio" name="single_name" value="1" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                    <span class="ml-2 text-white/70">Un nombre para todos</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="single_name" value="0" x-model="singleName" class="text-[#e50914] focus:ring-[#e50914] bg-black/60">
                    <span class="ml-2 text-white/70">Un nombre por ticket</span>
                </label>

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
                                        class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white focus:ring-2 focus:ring-[#e50914]">
                                    <template x-for="sid in selectedIds" :key="sid">
                                        <option :value="sid" :selected="sid === (oldSeatFor[i+1] ? parseInt(oldSeatFor[i+1], 10) : id)" x-text="seatsMap[sid] ? seatsMap[sid].label : sid"></option>
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
