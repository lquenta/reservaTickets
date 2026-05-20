@extends('layouts.admin')

@section('title', 'Reprogramar - ' . $event->name)

@section('admin')
<nav class="mb-6 text-sm text-slate-600 dark:text-slate-400">
    <a href="{{ route('admin.events.show', $event) }}" class="hover:text-violet-600 dark:hover:text-violet-400">← {{ $event->name }}</a>
</nav>

<div class="max-w-xl">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mb-2">Reprogramar evento</h1>
    <p class="text-slate-600 dark:text-slate-400 mb-6">{{ $event->name }}</p>

    <div class="rounded-2xl border-2 border-amber-200/60 dark:border-amber-700/50 bg-amber-50 dark:bg-amber-900/20 p-4 mb-6 text-sm text-amber-900 dark:text-amber-100">
        <p class="font-semibold mb-2">Impacto</p>
        <ul class="list-disc list-inside space-y-1">
            <li>Fecha actual: <strong>{{ $event->starts_at->translatedFormat('d/m/Y H:i') }}</strong></li>
            <li>{{ $confirmedCount }} reservas confirmadas y {{ $pendingCount }} pendientes de pago no se modifican.</li>
            <li>Los PDF y correos de tickets muestran la nueva fecha del evento al regenerarse.</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('admin.events.reschedule.store', $event) }}" class="rounded-3xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-8 shadow-xl space-y-5">
        @csrf
        <div>
            <label for="starts_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nueva fecha y hora</label>
            <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at->format('Y-m-d\TH:i')) }}" required
                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">
            @error('starts_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="reason" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Motivo (opcional)</label>
            <textarea id="reason" name="reason" rows="3" maxlength="2000" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2">{{ old('reason') }}</textarea>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-gradient-to-r from-violet-600 to-fuchsia-600 px-6 py-2 text-white font-medium">Confirmar reprogramación</button>
            <a href="{{ route('admin.events.show', $event) }}" class="rounded-lg border border-slate-300 dark:border-slate-600 px-6 py-2 text-slate-700 dark:text-slate-300">Cancelar</a>
        </div>
    </form>
</div>
@endsection
