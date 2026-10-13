@php
    $reservations = $u->reservations;
@endphp

@if($reservations->isEmpty())
    <p class="text-sm text-slate-500 dark:text-slate-400">Este usuario no tiene reservas.</p>
@else
    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Reservas de {{ $u->name }}</p>
    <ul class="space-y-2">
            @foreach($reservations as $r)
                <li class="rounded-xl border border-violet-200/70 dark:border-violet-700/40 bg-white dark:bg-slate-800/80 p-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-800 dark:text-white">{{ $r->event?->name ?? 'Evento' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $r->created_at?->translatedFormat('d/m/Y H:i') }}
                                @if($r->payment_code)
                                    · Código {{ $r->payment_code }}
                                @endif
                                @if($r->sale_amount !== null)
                                    · {{ number_format((float) $r->sale_amount, 2, ',', '.') }} Bs
                                @endif
                            </p>
                            @if($r->sale_type === 'surrogate')
                                <span class="inline-flex mt-1 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 text-xs font-medium">Venta surrogada</span>
                            @elseif($r->sale_type === 'honored_guest')
                                <span class="inline-flex mt-1 rounded-full bg-[#a569ff]/20 dark:bg-purple-900/40 text-[#4B0082] dark:text-[#a569ff] px-2 py-0.5 text-xs font-medium">Invitado de honor</span>
                            @endif
                            @if($r->soldBy)
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Vendido/invitado por: <strong>{{ $r->soldBy->name }}</strong></p>
                            @endif
                            <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                                @forelse($r->reservationTickets as $t)
                                    {{ $t->holder_name }}{{ $t->seat ? ' ('.$t->seat->display_label.')' : '' }}{{ !$loop->last ? ', ' : '' }}
                                @empty
                                    Sin entradas asignadas
                                @endforelse
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            @if($r->status === 'INICIADO')
                                <span class="inline-flex rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 px-3 py-1 text-xs font-medium">En proceso</span>
                            @elseif($r->status === 'PENDIENTE_PAGO')
                                <span class="inline-flex rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200 px-3 py-1 text-xs font-medium">Pendiente de pago</span>
                            @elseif($r->status === 'CONFIRMADO')
                                <span class="inline-flex rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200 px-3 py-1 text-xs font-medium">Confirmado</span>
                            @elseif($r->status === 'REEMBOLSADO')
                                <span class="inline-flex rounded-full bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-200 px-3 py-1 text-xs font-medium">Reembolsado</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-400 px-3 py-1 text-xs font-medium">Cancelado</span>
                            @endif
                            @if($r->status === 'CONFIRMADO')
                                <a href="{{ route('admin.reservations.tickets-pdf', $r) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-violet-600 dark:text-violet-400 hover:underline">Ver PDF</a>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
    </ul>
@endif
