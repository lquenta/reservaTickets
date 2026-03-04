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

@if(empty($seats))
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

                <div class="flex flex-col gap-3 items-center">
                    @foreach($seatsByRow as $row => $rowSeats)
                        @php $rowLetter = $rowSeats->first()->row_letter ?? chr(64 + (int)$row); @endphp
                        <div class="flex gap-3 items-center justify-center w-full" style="max-width: 100%;">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-red-900/50 bg-black/40 text-sm font-bold text-[#e50914]" aria-label="Fila {{ $rowLetter }}">{{ $rowLetter }}</span>
                            <div class="flex gap-2 justify-center flex-wrap">
                                @foreach($rowSeats as $seat)
                                    <button type="button"
                                            @click="toggle({{ json_encode($seat) }})"
                                            :disabled="!canSelect({{ json_encode($seat) }})"
                                            :class="{
                                                'bg-emerald-600 hover:bg-emerald-500 text-white': isAvailable({{ $seat->id }}) && !isBlocked({{ json_encode($seat) }}) && !isSelected({{ $seat->id }}),
                                                'bg-[#e50914] text-white ring-2 ring-white': isSelected({{ $seat->id }}),
                                                'bg-slate-700 text-slate-500 cursor-not-allowed': isBlocked({{ json_encode($seat) }}) || (!isAvailable({{ $seat->id }}) && !isSelected({{ $seat->id }}))
                                            }"
                                            class="w-10 h-10 rounded-lg font-mono text-sm font-bold transition shrink-0 disabled:opacity-70"
                                            title="Fila {{ $seat->row_letter }} Butaca {{ $seat->number }} {{ $seat->blocked ? '(bloqueada)' : '' }}">
                                        {{ $seat->number }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
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
