@extends('layouts.admin')

@section('title', 'Butacas ocupadas - ' . $event->name)

@section('admin')
<div class="mb-8">
    <a href="{{ route('admin.events.index') }}" class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 font-medium transition mb-4">
        &larr; Volver a eventos
    </a>
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Butacas ocupadas</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">{{ $event->name }} — {{ $event->starts_at->translatedFormat('d/m/Y H:i') }}</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-lg max-w-4xl">
    <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">El color corresponde al <strong>sector</strong> del venue (misma convención que el checkout). Entre butacas del mismo sector, solo cambia la <strong>opacidad</strong>: plena = disponible para el público; atenuada = ocupada o bloqueada.</p>
    <div class="flex flex-wrap gap-6 mb-6 text-sm items-center">
        <span class="inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg flex items-center justify-center font-mono text-xs font-bold border-2 box-border" style="background-color:{{ $sectionSeatPalette[0]['bg'] }};border-color:{{ $sectionSeatPalette[0]['border'] }};color:{{ $sectionSeatPalette[0]['text'] }};opacity:1;">1</span>
            Disponible
        </span>
        <span class="inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg flex items-center justify-center font-mono text-xs font-bold border-2 box-border" style="background-color:{{ $sectionSeatPalette[0]['bg'] }};border-color:{{ $sectionSeatPalette[0]['border'] }};color:{{ $sectionSeatPalette[0]['text'] }};opacity:0.42;">1</span>
            No disponible (ocupada o bloqueada)
        </span>
        <span class="text-slate-500 dark:text-slate-400">Haz clic en una butaca disponible para bloquearla solo en este evento, o en una bloqueada por evento para desbloquearla.</span>
    </div>

    @if(isset($layoutElements) && $layoutElements->isNotEmpty())
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 text-center">Plano WYSIWYG guardado</p>
        <div class="relative w-full rounded-xl border border-slate-300 dark:border-slate-600 overflow-auto bg-[radial-gradient(circle,_rgba(148,163,184,0.25)_1px,_transparent_1px)] bg-[size:16px_16px]" style="min-height:820px;">
            @foreach($layoutElements->sortBy('z_index') as $el)
                @if(($el['type'] ?? null) === 'seat' && !empty($el['seat']))
                    @php
                        $seat = $el['seat'];
                        $occupied = (bool) ($seat['occupied'] ?? false);
                        $blockedGlobal = (bool) ($seat['blocked_globally'] ?? false);
                        $blockedForEvent = (bool) ($seat['blocked_for_event'] ?? false);
                        $blockedAny = $blockedGlobal || $blockedForEvent;
                        $unavailable = $occupied || $blockedAny;
                        $sid = (int) ($seat['section_id'] ?? 0);
                        $pal = $sectionSeatPalette[abs($sid) % count($sectionSeatPalette)];
                        $op = $unavailable ? 0.42 : 1;
                        $fillStyle = 'background-color:'.$pal['bg'].';border:2px solid '.$pal['border'].';color:'.$pal['text'].';opacity:'.$op.';';
                        $baseStyle = 'position:absolute;left:'.(float) ($el['x'] ?? 0).'px;top:'.(float) ($el['y'] ?? 0).'px;width:'.(float) ($el['w'] ?? 52).'px;height:'.(float) ($el['h'] ?? 52).'px;transform:rotate('.(float) ($el['rotation'] ?? 0).'deg);z-index:'.(int) ($el['z_index'] ?? 0).';';
                    @endphp
                    @if($occupied || $blockedGlobal)
                        <span class="rounded-md text-xs font-semibold flex items-center justify-center box-border cursor-default"
                              style="{{ $baseStyle }}{{ $fillStyle }}"
                              title="Butaca {{ $seat['label'] }}{{ $occupied ? ' (ocupada)' : '' }}{{ $blockedAny ? ' (bloqueada)' : '' }}">
                            {{ $seat['label'] }}
                        </span>
                    @elseif($blockedForEvent)
                        <form method="POST" action="{{ route('admin.events.seats.unblock', [$event, $seat['id']]) }}" style="{{ $baseStyle }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full h-full rounded-md text-xs font-semibold box-border transition hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-800"
                                    style="{{ $fillStyle }}"
                                    title="Butaca {{ $seat['label'] }} bloqueada para evento (clic para desbloquear)">
                                {{ $seat['label'] }}
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.events.seats.block', [$event, $seat['id']]) }}" style="{{ $baseStyle }}">
                            @csrf
                            <button type="submit" class="w-full h-full rounded-md text-xs font-semibold box-border transition hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-800"
                                    style="{{ $fillStyle }}"
                                    title="Butaca {{ $seat['label'] }} disponible (clic para bloquear)">
                                {{ $seat['label'] }}
                            </button>
                        </form>
                    @endif
                @elseif(($el['type'] ?? null) === 'stage')
                    <div class="absolute rounded-md text-xs font-semibold px-2 py-1 bg-red-700 text-white flex items-center justify-center"
                         style="left:{{ (float) ($el['x'] ?? 0) }}px;top:{{ (float) ($el['y'] ?? 0) }}px;width:{{ (float) ($el['w'] ?? 120) }}px;height:{{ (float) ($el['h'] ?? 48) }}px;transform:rotate({{ (float) ($el['rotation'] ?? 0) }}deg);z-index:{{ (int) ($el['z_index'] ?? 0) }};"
                         title="Escenario">
                        {{ data_get($el, 'meta.label', 'ESCENARIO') }}
                    </div>
                @elseif(($el['type'] ?? null) === 'speaker')
                    <div class="absolute rounded-md text-xs font-semibold px-2 py-1 bg-amber-600 text-white flex items-center justify-center"
                         style="left:{{ (float) ($el['x'] ?? 0) }}px;top:{{ (float) ($el['y'] ?? 0) }}px;width:{{ (float) ($el['w'] ?? 80) }}px;height:{{ (float) ($el['h'] ?? 48) }}px;transform:rotate({{ (float) ($el['rotation'] ?? 0) }}deg);z-index:{{ (int) ($el['z_index'] ?? 0) }};"
                         title="Parlante">
                        {{ data_get($el, 'meta.label', 'PARLANTE') }}
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 text-center">Escenario</p>
        <div class="h-2 rounded bg-violet-300/50 dark:bg-violet-700/50 mb-8 mx-auto max-w-md"></div>

        @php
            $maxCols = $seatsByRow->isEmpty() ? 1 : $seatsByRow->max(fn ($r) => $r->count());
            $labelW = 2.5;
            $gapLabel = 0.75;
            $gapSeat = 0.5;
            $paddingVw = 5;
        @endphp
        {{-- Plano escalado al viewport: todas las butacas visibles, proporción correcta --}}
        <div class="w-full seat-plan-grid overflow-hidden" style="--cols: {{ $maxCols }}; --seat-size: min(2.5rem, max(1rem, calc((100vw - {{ $paddingVw }}rem - {{ $labelW }}rem - {{ $gapLabel }}rem - (var(--cols) - 1) * {{ $gapSeat }}rem) / var(--cols))));">
        <div class="flex flex-col gap-3 items-center">
        @foreach($seatsByRow as $row => $rowSeats)
            @php $rowLetter = $rowSeats->first()->row_letter ?? chr(64 + (int)$row); @endphp
            <div class="flex gap-3 items-center justify-center flex-nowrap">
                <span class="seat-plan-label flex shrink-0 items-center justify-center rounded-lg border border-violet-300/60 dark:border-violet-600/60 bg-slate-100 dark:bg-slate-700 font-bold text-violet-700 dark:text-violet-300" style="width: var(--seat-size); height: var(--seat-size); font-size: min(0.875rem, var(--seat-size)); line-height: 1;" aria-label="Fila {{ $rowLetter }}">{{ $rowLetter }}</span>
                <div class="flex gap-2 justify-center flex-nowrap shrink-0">
                    @foreach($rowSeats as $seat)
                        @php
                            $occupied = $occupiedSeatIds->has($seat->id);
                            $blockedGlobally = $seat->blocked ?? false;
                            $blockedForEvent = isset($blockedSeatIds) ? $blockedSeatIds->has($seat->id) : false;
                            $blockedAny = $blockedGlobally || $blockedForEvent;
                            $unavailable = $occupied || $blockedAny;
                            $sid = (int) ($seat->section_id ?? 0);
                            $pal = $sectionSeatPalette[abs($sid) % count($sectionSeatPalette)];
                            $op = $unavailable ? 0.42 : 1;
                            $seatFill = 'background-color:'.$pal['bg'].';border:2px solid '.$pal['border'].';color:'.$pal['text'].';opacity:'.$op.';';
                            $seatSize = 'width: var(--seat-size); height: var(--seat-size); min-width: var(--seat-size); font-size: min(0.875rem, var(--seat-size)); line-height: 1;';
                        @endphp
                        @if($occupied || $blockedGlobally)
                            <span class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 box-border cursor-default"
                                  style="{{ $seatSize }}{{ $seatFill }}"
                                  title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }}{{ $blockedAny ? ' (bloqueada)' : '' }}{{ $occupied ? ' (ocupada)' : '' }}">
                                {{ $seat->number }}
                            </span>
                        @elseif($blockedForEvent)
                            <form method="POST" action="{{ route('admin.events.seats.unblock', [$event, $seat]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 transition box-border hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-800"
                                        style="{{ $seatSize }}{{ $seatFill }}"
                                        title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }} (bloqueada para evento, clic para desbloquear)">
                                    {{ $seat->number }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.events.seats.block', [$event, $seat]) }}">
                                @csrf
                                <button type="submit"
                                        class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 transition box-border hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-800"
                                        style="{{ $seatSize }}{{ $seatFill }}"
                                        title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }} (disponible, clic para bloquear)">
                                    {{ $seat->number }}
                                </button>
                            </form>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
