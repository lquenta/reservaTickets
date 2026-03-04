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
    </div>

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 text-center">Escenario</p>
    <div class="h-2 rounded bg-violet-300/50 dark:bg-violet-700/50 mb-8 mx-auto max-w-md"></div>

    <div class="flex flex-col gap-3 items-center">
        @foreach($seatsByRow as $row => $rowSeats)
            @php $rowLetter = $rowSeats->first()->row_letter ?? chr(64 + (int)$row); @endphp
            <div class="flex gap-3 items-center justify-center w-full">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-violet-300/60 dark:border-violet-600/60 bg-slate-100 dark:bg-slate-700 text-sm font-bold text-violet-700 dark:text-violet-300" aria-label="Fila {{ $rowLetter }}">{{ $rowLetter }}</span>
                <div class="flex gap-2 justify-center flex-wrap">
                    @foreach($rowSeats as $seat)
                        @php
                            $occupied = $occupiedSeatIds->has($seat->id);
                            $blocked = $seat->blocked ?? false;
                            if ($blocked) {
                                $class = 'bg-slate-500/70 text-slate-300 dark:bg-slate-600/80 dark:text-slate-400 cursor-default';
                            } elseif ($occupied) {
                                $class = 'bg-red-500/90 text-white dark:bg-red-600';
                            } else {
                                $class = 'bg-emerald-500/90 text-white dark:bg-emerald-600';
                            }
                        @endphp
                        <span class="w-10 h-10 rounded-lg font-mono text-sm font-bold flex items-center justify-center shrink-0 {{ $class }}"
                              title="Fila {{ $seat->row_letter ?? $rowLetter }} Butaca {{ $seat->number }}{{ $blocked ? ' (bloqueada)' : '' }}{{ $occupied ? ' (ocupada)' : ' (disponible)' }}">
                            {{ $seat->number }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
