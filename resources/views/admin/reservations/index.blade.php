@extends('layouts.admin')

@section('title', 'Reservas - Admin')

@section('admin')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Reservas</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">Filtra por estado y autoriza el envío de tickets.</p>
</div>

<form method="GET" class="flex flex-wrap items-center gap-3 mb-6 p-4 rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80">
    <label for="status" class="text-sm font-medium text-slate-700 dark:text-slate-300">Estado</label>
    <select id="status" name="status" class="rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500">
        <option value="">Todos los estados</option>
        <option value="INICIADO" {{ request('status') === 'INICIADO' ? 'selected' : '' }}>En proceso</option>
        <option value="PENDIENTE_PAGO" {{ request('status') === 'PENDIENTE_PAGO' ? 'selected' : '' }}>Pendiente de pago</option>
        <option value="CONFIRMADO" {{ request('status') === 'CONFIRMADO' ? 'selected' : '' }}>Confirmado</option>
        <option value="CANCELADO" {{ request('status') === 'CANCELADO' ? 'selected' : '' }}>Cancelado</option>
    </select>
    <button type="submit" class="rounded-xl bg-violet-600 px-4 py-2.5 text-white font-semibold hover:bg-violet-700 transition">Filtrar</button>
</form>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px]">
            <thead class="bg-slate-100 dark:bg-slate-700/50">
                <tr>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Usuario / Evento / Butacas</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Estado</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Última acción</th>
                    <th class="text-left px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Comprobante</th>
                    <th class="text-right px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservations as $r)
                    <tr class="border-t border-slate-200 dark:border-slate-700 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                        <td class="px-5 py-4">
                            <p class="font-medium text-slate-800 dark:text-white">{{ $r->user->name }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $r->user->email }}</p>
                            <p class="text-sm text-violet-600 dark:text-violet-400 font-medium mt-0.5">{{ $r->event->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                @foreach($r->reservationTickets as $t)
                                    {{ $t->holder_name }}{{ $t->seat ? ' (' . $t->seat->display_label . ')' : '' }}{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </p>
                        </td>
                        <td class="px-5 py-4">
                            @if($r->status === 'INICIADO')
                                <span class="inline-flex rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 px-3 py-1 text-sm font-medium">En proceso</span>
                            @elseif($r->status === 'PENDIENTE_PAGO')
                                <span class="inline-flex rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200 px-3 py-1 text-sm font-medium">Pendiente de pago</span>
                            @elseif($r->status === 'CONFIRMADO')
                                <span class="inline-flex rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200 px-3 py-1 text-sm font-medium">Confirmado</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400 px-3 py-1 text-sm font-medium">Cancelado</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-sm text-slate-700 dark:text-slate-300" title="{{ $r->updated_at->isoFormat('LLLL') }}">{{ $r->updated_at->translatedFormat('d/m/Y H:i') }}</p>
                            @if($r->status === 'INICIADO' && $r->expires_at)
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">Expira: {{ $r->expires_at->translatedFormat('H:i') }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($r->payment_receipt_path)
                                <a href="{{ asset('storage/'.$r->payment_receipt_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition">
                                    <span aria-hidden="true">🖼️</span> Ver comprobante
                                </a>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if($r->status === 'INICIADO')
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.reservations.cancel', $r) }}" class="inline" onsubmit="return confirm('¿Cancelar esta reserva en proceso? Las butacas quedarán liberadas.');">
                                        @csrf
                                        <button type="submit" class="rounded-xl bg-red-600 px-4 py-2 text-white font-semibold hover:bg-red-700 transition">Cancelar</button>
                                    </form>
                                </div>
                            @elseif($r->status === 'PENDIENTE_PAGO')
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.reservations.authorize', $r) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-white font-semibold hover:bg-emerald-700 transition">Autorizar y enviar tickets</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.reservations.reject', $r) }}" class="inline" onsubmit="return confirm('¿Rechazar esta reserva? El comprobante no será aceptado y las butacas quedarán liberadas.');">
                                        @csrf
                                        <button type="submit" class="rounded-xl bg-red-600 px-4 py-2 text-white font-semibold hover:bg-red-700 transition">Rechazar</button>
                                    </form>
                                </div>
                            @elseif($r->status === 'CONFIRMADO')
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="{{ route('admin.reservations.tickets-pdf', $r) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl border-2 border-violet-500 px-4 py-2 text-sm font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/30 transition">
                                        <span aria-hidden="true">📄</span> Ver PDF
                                    </a>
                                    <a href="{{ route('admin.reservations.tickets-pdf', ['reservation' => $r, 'download' => 1]) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 transition">
                                        <span aria-hidden="true">⬇️</span> Descargar
                                    </a>
                                    <form method="POST" action="{{ route('admin.reservations.resend-tickets', $r) }}" class="inline" onsubmit="return confirm('¿Reenviar los boletos por correo a {{ $r->user->email }}?');">
                                        @csrf
                                        <button type="submit" class="rounded-xl bg-slate-600 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition">Reenviar tickets</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 text-sm">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">No hay reservas con los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
        {{ $reservations->links() }}
    </div>
</div>
@endsection
