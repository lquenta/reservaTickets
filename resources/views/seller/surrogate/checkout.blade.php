@php
    $client = $reservation->user;
    $confirmRoute = $flow->route('surrogate-sale.checkout.confirm', $reservation);
@endphp
{{-- Paso 2 — misma experiencia visual que checkout del cliente --}}
<div class="max-w-2xl mx-auto mb-6 sm:mb-8 px-1">
    <a href="{{ route($flow->checkoutSuccessRoute) }}" class="text-sm text-[#e11d8a] hover:text-[#22d3ee]">← Volver a eventos</a>
</div>

<div class="max-w-2xl mx-auto mb-6 sm:mb-8 px-1">
    <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-2 sm:gap-x-4">
        <div class="flex items-center gap-2 min-w-0">
            <span class="flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-full border-2 border-[#e11d8a]/60 bg-[#e11d8a]/20 text-xs sm:text-sm font-medium text-[#e11d8a]">1</span>
            <span class="text-xs sm:text-sm text-white/50"><span class="sm:hidden">Butacas / datos</span><span class="hidden sm:inline">Elige butacas / datos</span></span>
        </div>
        <div class="h-px w-8 sm:w-12 bg-fuchsia-900/50 shrink-0" aria-hidden="true"></div>
        <div class="flex items-center gap-2 min-w-0">
            <span class="flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-full bg-[#e11d8a] text-xs sm:text-sm font-bold text-white">2</span>
            <span class="text-xs sm:text-sm font-medium text-[#e11d8a]"><span class="sm:hidden">Comprobante</span><span class="hidden sm:inline">Comprobante de pago</span></span>
        </div>
    </div>
</div>

<div class="max-w-2xl mx-auto px-1" x-data="checkoutTimer({{ $reservation->expires_at->timestamp * 1000 }})" x-init="start()">
    <div class="rounded-2xl border border-amber-500/50 bg-amber-900/20 px-4 py-5 sm:p-6 mb-6 sm:mb-8 text-center">
        <p class="text-sm font-semibold text-amber-200 mb-2 uppercase tracking-wide">Tiempo restante para completar la venta</p>
        <p class="text-3xl sm:text-4xl md:text-5xl font-mono font-bold text-amber-100 tabular-nums" x-text="display" x-transition></p>
    </div>

    <div class="rounded-2xl border border-violet-500/40 bg-violet-900/20 px-4 py-4 mb-6">
        <p class="font-semibold text-violet-100">Venta surrogada — {{ $client->name }}</p>
        @if($client->isGuest())
            <p class="text-sm text-amber-200/90 mt-1">Invitado temporal — los tickets llegarán a tu correo ({{ $reservation->soldBy?->email }}) al autorizar. Tú entregas los tickets al invitado.</p>
        @else
            <p class="text-sm text-violet-200/80 mt-1">{{ $client->email }} · {{ $client->phone }}
                @if($client->hasVerifiedEmail())
                    <span class="text-emerald-400">(email verificado)</span>
                @else
                    <span class="text-amber-300">(email sin verificar)</span>
                @endif
            </p>
            <p class="text-sm text-violet-200/70 mt-2">Los tickets se enviarán por correo cuando un administrador autorice el pago.</p>
        @endif
    </div>

    <div class="rounded-2xl border border-fuchsia-900/50 bg-black/60 backdrop-blur px-4 py-6 sm:p-8 md:p-10 space-y-8">
        <h1 class="font-display text-2xl md:text-3xl font-bold text-[#e11d8a] tracking-widest">CHECKOUT — PASO 2</h1>
        <p class="text-xl text-white/80 mb-2">{{ $reservation->event->name }}</p>
        <p class="text-white/60 text-sm mb-6">Resumen de la reserva del cliente y comprobante de pago.</p>

        @include('checkout._layout-map-readonly', ['checkoutMap' => $checkoutMap ?? null])

        @php
            $ticketsWithSection = $reservation->reservationTickets->filter(fn ($t) => $t->seat || $t->section);
        @endphp
        @if($ticketsWithSection->isNotEmpty())
        <div class="rounded-2xl border-2 border-[#e11d8a]/60 bg-[#e11d8a]/10 p-6">
            <h2 class="font-display text-xl font-bold text-[#e11d8a] tracking-wider mb-4">ENTRADAS DEL CLIENTE</h2>
            <ul class="space-y-3">
                @foreach($reservation->reservationTickets as $t)
                    <li class="flex flex-wrap items-center gap-3 text-white">
                        <span class="font-medium">{{ $t->holder_name }}</span>
                        @if($t->seat)
                            <span class="inline-flex items-center rounded-lg bg-[#e11d8a] px-3 py-1.5 text-sm font-mono font-bold text-white">Butaca {{ $t->seat->display_label }}</span>
                        @elseif($t->section)
                            <span class="inline-flex items-center rounded-lg bg-[#e11d8a]/80 px-3 py-1.5 text-sm font-medium text-white">Sección {{ $t->section->name }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="rounded-xl bg-black/40 border border-fuchsia-900/30 p-4">
            <h2 class="font-semibold text-white/90 mb-2">Resumen</h2>
            <p class="text-white/70 mb-3">{{ $reservation->reservationTickets->count() }} ticket(s)</p>
            @if(isset($totalPrice) && $totalPrice > 0)
            <p class="text-white/90 font-semibold mb-3">Costo total: <span class="block mt-1 tabular-nums">{{ number_format($totalPrice, 2, ',', '.') }} Bs</span></p>
            @endif
        </div>

        @if($reservation->event->qr_image_path)
            <div class="rounded-2xl bg-black/40 border border-fuchsia-900/30 p-6 text-center">
                <p class="text-sm font-semibold text-white/80 mb-4">Realice el pago escaneando el código QR</p>
                <img src="{{ asset('storage/'.$reservation->event->qr_image_path) }}" alt="QR de pago" class="mx-auto max-w-[220px] h-auto rounded-xl border border-fuchsia-900/50">
            </div>
        @else
            <div class="rounded-2xl border border-fuchsia-900/50 bg-fuchsia-900/10 p-6">
                <p class="text-white/80 font-medium">Realice el pago por el medio indicado por el organizador. Luego suba la captura o foto del comprobante abajo.</p>
            </div>
        @endif

        <form method="POST" action="{{ $confirmRoute }}" enctype="multipart/form-data" class="space-y-5 pt-2">
            @csrf
            <div class="p-4 rounded-xl border border-fuchsia-900/50">
                <label for="payment_receipt" class="block font-semibold text-white/80 mb-2">Comprobante de pago (captura o foto) <span class="text-fuchsia-300">*</span></label>
                <input id="payment_receipt" type="file" name="payment_receipt" accept="image/*" required
                       class="w-full rounded-xl border border-fuchsia-900/50 bg-black/60 px-4 py-3 text-white file:mr-4 file:rounded-lg file:border-0 file:bg-[#e11d8a] file:px-4 file:py-2 file:text-white file:font-medium @error('payment_receipt') border-red-500 @enderror">
                <p class="mt-1 text-xs text-white/50">Formatos: JPG, PNG, etc. Máximo 5 MB.</p>
                @error('payment_receipt')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-fuchsia-900/50 hover:border-[#e11d8a]/50 transition cursor-pointer">
                <input type="checkbox" name="accept_terms" value="1" required class="mt-1 rounded border-fuchsia-900/50 text-[#e11d8a] focus:ring-[#22d3ee] bg-black/60 size-5">
                <span class="text-white/80">Acepto los <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="text-[#e11d8a] hover:text-[#22d3ee] underline underline-offset-2">términos y condiciones</a>.</span>
            </label>
            @error('accept_terms')<p class="text-sm text-red-400">{{ $message }}</p>@enderror

            @if(!$client->isGuest() && !$client->hasVerifiedEmail())
            <div class="rounded-xl border-2 border-amber-500/50 bg-amber-900/20 p-4">
                <label class="flex items-start gap-3 text-sm text-amber-100 cursor-pointer">
                    <input type="checkbox" name="seller_delivery_responsibility" value="1" required class="mt-1 rounded border-amber-600/50 text-[#e11d8a] focus:ring-[#22d3ee] bg-black/60 size-5">
                    <span>Asumo la responsabilidad de que el cliente reciba sus tickets tras la autorización si el envío por correo no llega o el correo no está verificado.</span>
                </label>
                @error('seller_delivery_responsibility')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            @endif

            <button type="submit" class="w-full rounded-2xl bg-[#e11d8a] px-6 py-4 text-white font-bold text-lg hover:bg-fuchsia-700 transition">
                Confirmar y enviar a revisión
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function checkoutTimer(expiresAtMs) {
    return {
        display: '10:00',
        interval: null,
        start() {
            const self = this;
            function tick() {
                const left = Math.max(0, Math.floor((expiresAtMs - Date.now()) / 1000));
                const m = Math.floor(left / 60);
                const s = left % 60;
                self.display = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                if (left <= 0) {
                    clearInterval(self.interval);
                    window.location.href = '{{ route($flow->checkoutSuccessRoute) }}';
                }
            }
            tick();
            this.interval = setInterval(tick, 1000);
        }
    };
}
</script>
@endpush
