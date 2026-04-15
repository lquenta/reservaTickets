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
    <div class="flex flex-wrap gap-6 mb-6 text-sm">
        <span class="inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-red-500/90 text-white flex items-center justify-center font-mono text-xs font-bold">1</span>
            Ocupada
        </span>
        <span class="inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-emerald-500/90 text-white flex items-center justify-center font-mono text-xs font-bold">1</span>
            Disponible
        </span>
        <span class="inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-slate-500/70 text-slate-300 flex items-center justify-center font-mono text-xs font-bold">1</span>
            Bloqueada
        </span>
        <span class="text-slate-500 dark:text-slate-400">Haz clic en butacas disponibles o bloqueadas para alternar el bloqueo por evento.</span>
    </div>

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
                            $blocked = $blockedGlobally || $blockedForEvent;
                            if ($blocked) {
                                $class = 'bg-slate-500/70 text-slate-300 dark:bg-slate-600/80 dark:text-slate-400 cursor-default';
                            } elseif ($occupied) {
                                $class = 'bg-red-500/90 text-white dark:bg-red-600';
                            } else {
                                $class = 'bg-emerald-500/90 text-white dark:bg-emerald-600';
                            }
                        @endphp
                        @if($occupied || $blockedGlobally)
                            <span class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 {{ $class }}"
                                  style="width: var(--seat-size); height: var(--seat-size); min-width: var(--seat-size); font-size: min(0.875rem, var(--seat-size)); line-height: 1;"
                                  title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }}{{ $blocked ? ' (bloqueada)' : '' }}{{ $occupied ? ' (ocupada)' : ' (disponible)' }}">
                                {{ $seat->number }}
                            </span>
                        @elseif($blockedForEvent)
                            <form method="POST" action="{{ route('admin.events.seats.unblock', [$event, $seat]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 transition hover:opacity-90 {{ $class }}"
                                        style="width: var(--seat-size); height: var(--seat-size); min-width: var(--seat-size); font-size: min(0.875rem, var(--seat-size)); line-height: 1;"
                                        title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }} (bloqueada para evento, clic para desbloquear)">
                                    {{ $seat->number }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.events.seats.block', [$event, $seat]) }}">
                                @csrf
                                <button type="submit"
                                        class="seat-plan-cell rounded-lg font-mono font-bold flex items-center justify-center shrink-0 transition hover:opacity-90 {{ $class }}"
                                        style="width: var(--seat-size); height: var(--seat-size); min-width: var(--seat-size); font-size: min(0.875rem, var(--seat-size)); line-height: 1;"
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
</div>
@endsection
