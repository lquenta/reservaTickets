@php
    $readonly = $readonly ?? false;
    $maxCols = \App\Support\EventSeatOverviewMapData::maxColsForGrid($seatsByRow);
    $labelW = 2.5;
    $gapLabel = 0.75;
    $gapSeat = 0.5;
    $paddingVw = 5;
    $alpineConfigJson = json_encode([
        'layoutElements' => $layoutElementsData ?? [],
        'layoutCanvas' => $layoutCanvas ?? ['width' => null, 'height' => null],
        'sectionPalettesById' => $sectionPalettesById ?? [],
        'readonly' => $readonly,
    ], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp

<div class="mx-auto w-full min-w-0 max-w-full px-2 sm:max-w-4xl sm:px-0 relative z-0"
     x-data="adminEventSeatsMap({{ $alpineConfigJson }})"
     @class(['pointer-events-none' => $readonly])>
    <div class="rounded-2xl border border-fuchsia-900/50 bg-black/60 backdrop-blur px-4 py-5 sm:p-6">
        <p class="text-white/70 text-sm mb-4 text-center">
            Mismo plano que en el checkout del cliente. El <strong class="text-white/90">color</strong> indica el sector;
            las butacas <strong class="text-white/90">grises</strong> están ocupadas o no disponibles para el público.
        </p>
        <div class="flex flex-wrap gap-4 sm:gap-6 mb-6 text-sm items-center justify-center text-white/80">
            <span class="inline-flex items-center gap-2">
                <span class="w-8 h-8 rounded-md flex items-center justify-center font-mono text-xs font-bold border-2 box-border"
                      style="background-color:{{ $legendSampleSeatStyle['bg'] }};border-color:{{ $legendSampleSeatStyle['border'] }};color:{{ $legendSampleSeatStyle['text'] }};">1</span>
                Disponible para el público
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="w-8 h-8 rounded-md flex items-center justify-center font-mono text-xs font-bold border-2 border-slate-600 bg-slate-800 text-slate-500">1</span>
                Ocupada o bloqueada
            </span>
            @if(!$readonly)
                <span class="inline-flex items-center gap-2">
                    <span class="w-8 h-8 rounded-md flex items-center justify-center font-mono text-xs font-bold border-2 border-slate-600 bg-slate-800 text-slate-400 ring-2 ring-amber-400/80">1</span>
                    Bloqueada solo en este evento (clic para desbloquear)
                </span>
            @else
                <span class="inline-flex items-center gap-2">
                    <span class="w-8 h-8 rounded-md flex items-center justify-center font-mono text-xs font-bold border-2 border-slate-600 bg-slate-800 text-slate-400 ring-2 ring-amber-400/80">1</span>
                    Bloqueada solo en este evento
                </span>
            @endif
        </div>
        @if(!$readonly)
            <p class="text-white/50 text-xs text-center mb-4">Clic en una butaca disponible para bloquearla solo en este evento.</p>
        @endif

        @if(!empty($hasCustomLayout))
            <p class="text-white/80 text-sm font-medium text-center mb-3">Plano del venue</p>
            <x-checkout-layout-map-frame>
                <template x-for="el in sortedLayoutElements" :key="el.id">
                    <div class="absolute isolate" :style="layoutElementWrapperStyle(el) + (readonly ? 'pointer-events:none;' : '')">
                        <template x-if="layoutElType(el) === 'seat' && el.seat">
                            <div class="absolute inset-0 z-10" :class="readonly ? 'pointer-events-none' : 'pointer-events-auto'">
                                <template x-if="readonly">
                                    <span class="absolute inset-0 rounded-md text-[9px] sm:text-[10px] font-bold px-0.5 sm:px-1 border-2 flex items-center justify-center leading-none overflow-hidden text-center cursor-default"
                                          :class="layoutSeatClass(el)"
                                          :style="layoutSeatFaceStyle(el)"
                                          :title="el.seat ? ('Butaca ' + el.seat.label) : ''">
                                        <span class="truncate max-w-full" x-text="el.seat ? el.seat.label : ''"></span>
                                    </span>
                                </template>
                                <template x-if="!readonly && seatUnavailable(el)">
                                    <span class="absolute inset-0 rounded-md text-[9px] sm:text-[10px] font-bold px-0.5 sm:px-1 border-2 flex items-center justify-center leading-none overflow-hidden text-center cursor-default"
                                          :class="layoutSeatClass(el)"
                                          :style="layoutSeatFaceStyle(el)"
                                          :title="'Butaca ' + el.seat.label + (el.seat.occupied ? ' (ocupada)' : ' (bloqueada)')">
                                        <span class="truncate max-w-full" x-text="el.seat.label"></span>
                                    </span>
                                </template>
                                <template x-if="!readonly && !seatUnavailable(el) && seatBlockedForEvent(el)">
                                    <form :action="el.seat.unblock_url" method="POST" class="absolute inset-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="w-full h-full rounded-md text-[9px] sm:text-[10px] font-bold px-0.5 sm:px-1 transition border-2 flex items-center justify-center leading-none overflow-hidden text-center"
                                                :class="layoutSeatClass(el)"
                                                :style="layoutSeatFaceStyle(el)"
                                                :title="'Butaca ' + el.seat.label + ' bloqueada para evento (clic para desbloquear)'">
                                            <span class="truncate max-w-full" x-text="el.seat.label"></span>
                                        </button>
                                    </form>
                                </template>
                                <template x-if="!readonly && seatPublicAvailable(el)">
                                    <form :action="el.seat.block_url" method="POST" class="absolute inset-0">
                                        @csrf
                                        <button type="submit"
                                                class="w-full h-full rounded-md text-[9px] sm:text-[10px] font-bold px-0.5 sm:px-1 transition border-2 flex items-center justify-center leading-none overflow-hidden text-center"
                                                :class="layoutSeatClass(el)"
                                                :style="layoutSeatFaceStyle(el)"
                                                :title="'Butaca ' + el.seat.label + ' disponible (clic para bloquear)'">
                                            <span class="truncate max-w-full" x-text="el.seat.label"></span>
                                        </button>
                                    </form>
                                </template>
                            </div>
                        </template>
                        <template x-if="layoutElType(el) === 'stage'">
                            <div class="absolute inset-0 z-0 flex items-center justify-center rounded-md border border-fuchsia-500/40 bg-fuchsia-700 px-0.5 text-white shadow-md pointer-events-none overflow-hidden"
                                 :style="layoutStageSpeakerFaceStyle()">
                                <span class="max-h-full overflow-hidden text-center text-[8px] font-semibold uppercase leading-tight sm:text-[10px]" x-text="(el.meta && el.meta.label) ? el.meta.label : 'ESCENARIO'"></span>
                            </div>
                        </template>
                        <template x-if="layoutElType(el) === 'speaker'">
                            <div class="absolute inset-0 z-0 flex items-center justify-center rounded-md border border-amber-400/40 bg-amber-600 px-0.5 text-white shadow-md pointer-events-none overflow-hidden"
                                 :style="layoutStageSpeakerFaceStyle()">
                                <span class="max-h-full overflow-hidden text-center text-[8px] font-semibold uppercase leading-tight sm:text-[10px]" x-text="(el.meta && el.meta.label) ? el.meta.label : 'PARLANTE'"></span>
                            </div>
                        </template>
                        <template x-if="layoutElType(el) === 'table'">
                            <div class="absolute inset-0 z-0 flex items-center justify-center rounded-xl border-2 border-amber-950/70 bg-amber-900 px-0.5 text-white shadow-md pointer-events-none overflow-hidden"
                                 :style="layoutStageSpeakerFaceStyle()">
                                <span class="max-h-full overflow-hidden text-center text-[8px] font-semibold uppercase leading-tight sm:text-[10px]" x-text="(el.meta && el.meta.label) ? el.meta.label : 'MESA'"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </x-checkout-layout-map-frame>
        @else
            <div class="w-full seat-plan-grid overflow-hidden" style="--cols: {{ $maxCols }}; --seat-size: clamp(0.875rem, calc((100vw - {{ $paddingVw }}rem - {{ $labelW }}rem - {{ $gapLabel }}rem - (var(--cols) - 1) * {{ $gapSeat }}rem) / var(--cols)), 3rem);">
                <div class="flex flex-col gap-3 items-center">
                    <div class="flex gap-3 items-end flex-nowrap mb-1">
                        <span class="shrink-0 invisible" style="width: var(--seat-size); height: var(--seat-size);" aria-hidden="true"></span>
                        <div class="flex items-end gap-2 shrink-0 min-w-0" style="width: calc(var(--seat-size) * {{ $maxCols }} + ({{ $maxCols }} - 1) * 0.5rem);">
                            <div class="flex flex-col items-center justify-center rounded-lg border border-amber-600/50 bg-amber-900/20 shrink-0 py-1 px-1.5 gap-0.5" style="width: var(--seat-size); min-height: var(--seat-size);" role="img" aria-label="Parlante">
                                <span class="text-[9px] sm:text-[10px] font-semibold text-amber-400/90 uppercase leading-tight"><span class="sm:hidden">P</span><span class="hidden sm:inline">PARLANTE</span></span>
                            </div>
                            <div class="flex-1 flex flex-col items-center justify-end gap-0.5 min-w-0 pb-0.5">
                                <div class="w-full rounded-sm bg-fuchsia-700 min-h-[3px]" style="height: 3px;" role="img" aria-label="Línea de escenario"></div>
                                <span class="text-[10px] sm:text-xs font-medium text-[#22d3ee] uppercase tracking-wider">ESCENARIO</span>
                            </div>
                            <div class="flex flex-col items-center justify-center rounded-lg border border-amber-600/50 bg-amber-900/20 shrink-0 py-1 px-1.5 gap-0.5" style="width: var(--seat-size); min-height: var(--seat-size);" role="img" aria-label="Parlante">
                                <span class="text-[9px] sm:text-[10px] font-semibold text-amber-400/90 uppercase leading-tight"><span class="sm:hidden">P</span><span class="hidden sm:inline">PARLANTE</span></span>
                            </div>
                        </div>
                    </div>
                    @foreach($seatsByRow as $row => $rowSeats)
                        @php $rowLetter = $rowSeats->first()->row_letter ?? chr(64 + (int)$row); @endphp
                        <div class="flex gap-3 items-center justify-center flex-nowrap">
                            <span class="seat-plan-label flex shrink-0 items-center justify-center rounded-lg border border-fuchsia-900/50 bg-black/40 font-bold text-[#e11d8a]" style="width: var(--seat-size); height: var(--seat-size); font-size: min(0.875rem, var(--seat-size)); line-height: 1;" aria-label="Fila {{ $rowLetter }}">{{ $rowLetter }}</span>
                            <div class="flex gap-2 justify-center flex-nowrap shrink-0">
                                @foreach($rowSeats as $seat)
                                    @php
                                        $occupied = $occupiedSeatIds->has($seat->id);
                                        $blockedGlobally = $seat->blocked ?? false;
                                        $blockedForEvent = $blockedSeatIds->has($seat->id);
                                        $unavailable = $occupied || $blockedGlobally;
                                        $sid = (int) ($seat->section_id ?? 0);
                                        $seatSize = 'width: var(--seat-size); height: var(--seat-size); min-width: var(--seat-size); font-size: min(0.875rem, var(--seat-size)); line-height: 1;';
                                    @endphp
                                    @if($unavailable)
                                        <span class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 box-border cursor-default border-2"
                                              style="{{ $seatSize }}background-color:#1e293b;border-color:#334155;color:#64748b;"
                                              title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }}{{ $occupied ? ' (ocupada)' : ' (bloqueada)' }}">
                                            {{ $seat->number }}
                                        </span>
                                    @elseif($blockedForEvent)
                                        @if($readonly)
                                            <span class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 box-border border-2 cursor-default"
                                                  style="{{ $seatSize }}background-color:#1e293b;border-color:#475569;color:#94a3b8;box-shadow:0 0 0 2px rgba(251,191,36,0.85);"
                                                  title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }} (bloqueada para evento)">
                                                {{ $seat->number }}
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('admin.events.seats.unblock', [$event, $seat]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 transition box-border border-2 hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e11d8a]"
                                                        style="{{ $seatSize }}background-color:#1e293b;border-color:#475569;color:#94a3b8;box-shadow:0 0 0 2px rgba(251,191,36,0.85);"
                                                        title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }} (bloqueada para evento, clic para desbloquear)">
                                                    {{ $seat->number }}
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        @php
                                            $pal = $sectionPaletteById[$sid] ?? ['bg' => '#2563eb', 'border' => '#1e40af', 'text' => '#ffffff'];
                                            $seatFill = 'background-color:'.$pal['bg'].';border-color:'.$pal['border'].';color:'.$pal['text'].';';
                                        @endphp
                                        @if($readonly)
                                            <span class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 box-border border-2 cursor-default"
                                                  style="{{ $seatSize }}{{ $seatFill }}"
                                                  title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }} (disponible)">
                                                {{ $seat->number }}
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('admin.events.seats.block', [$event, $seat]) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 transition box-border border-2 hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e11d8a]"
                                                        style="{{ $seatSize }}{{ $seatFill }}"
                                                        title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }} (disponible, clic para bloquear)">
                                                    {{ $seat->number }}
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
