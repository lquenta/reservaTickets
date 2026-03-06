@extends('layouts.app')

@section('title', 'Checkout - ' . $reservation->event->name)

@section('content')
{{-- Indicador de pasos del checkout --}}
<div class="max-w-2xl mx-auto mb-8">
    <div class="flex items-center justify-center gap-4">
        <div class="flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-[#e50914]/60 bg-[#e50914]/20 text-sm font-medium text-[#e50914]">1</span>
            <span class="text-sm text-white/50">Elige butacas / datos</span>
        </div>
        <div class="h-px w-12 bg-red-900/50" aria-hidden="true"></div>
        <div class="flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#e50914] text-sm font-bold text-white">2</span>
            <span class="text-sm font-medium text-[#e50914]">Comprobante de pago</span>
        </div>
    </div>
</div>

<div class="max-w-2xl mx-auto" x-data="checkoutTimer({{ $reservation->expires_at->timestamp * 1000 }})" x-init="start()">
    <div class="rounded-2xl border border-amber-500/50 bg-amber-900/20 p-6 mb-8 text-center">
        <p class="text-sm font-semibold text-amber-200 mb-2 uppercase tracking-wide">Tiempo restante para completar la reserva</p>
        <p class="text-4xl md:text-5xl font-mono font-bold text-amber-100 tabular-nums" x-text="display" x-transition></p>
    </div>

    <div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur p-8 md:p-10 space-y-8">
        <h1 class="font-display text-2xl md:text-3xl font-bold text-[#e50914] tracking-widest">CHECKOUT — PASO 2</h1>
        <p class="text-xl text-white/80 mb-2">{{ $reservation->event->name }}</p>
        <p class="text-white/60 text-sm mb-6">Resumen de tu reserva y comprobante de pago.</p>

        @php
            $ticketsWithSection = $reservation->reservationTickets->filter(fn ($t) => $t->seat || $t->section);
        @endphp
        @if($ticketsWithSection->isNotEmpty())
        <div class="rounded-2xl border-2 border-[#e50914]/60 bg-[#e50914]/10 p-6 mb-8">
            <h2 class="font-display text-xl font-bold text-[#e50914] tracking-wider mb-4">TUS ENTRADAS</h2>
            <p class="text-white/80 text-sm mb-4">Resumen de butacas y secciones elegidas:</p>
            <ul class="space-y-3">
                @foreach($reservation->reservationTickets as $t)
                    <li class="flex flex-wrap items-center gap-3 text-white">
                        <span class="font-medium">{{ $t->holder_name }}</span>
                        @if($t->seat)
                            <span class="inline-flex items-center rounded-lg bg-[#e50914] px-3 py-1.5 text-sm font-mono font-bold text-white">Butaca {{ $t->seat->display_label }}</span>
                        @elseif($t->section)
                            <span class="inline-flex items-center rounded-lg bg-[#e50914]/80 px-3 py-1.5 text-sm font-medium text-white">Sección {{ $t->section->name }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="rounded-xl bg-black/40 border border-red-900/30 p-4">
            <h2 class="font-semibold text-white/90 mb-2">Resumen</h2>
            <p class="text-white/70 mb-3">{{ $reservation->reservationTickets->count() }} ticket(s)</p>
            @if(isset($totalPrice) && $totalPrice > 0)
            <p class="cost-total-block text-white/90 font-semibold mb-3">Costo total: <span class="cost-total-price block mt-1">{{ number_format($totalPrice, 2, ',', '.') }} Bs</span></p>
            @endif
            <ul class="space-y-2">
                @foreach($reservation->reservationTickets as $t)
                    <li class="flex flex-wrap items-baseline gap-x-2 text-white/90">
                        <span class="font-medium">{{ $t->holder_name }}</span>
                        @if($t->seat)
                            <span class="inline-flex items-center rounded-md bg-[#e50914]/20 px-2 py-0.5 text-sm font-mono text-[#e50914]">Butaca {{ $t->seat->display_label }}</span>
                        @elseif($t->section)
                            <span class="inline-flex items-center rounded-md bg-[#e50914]/20 px-2 py-0.5 text-sm text-[#e50914]">Sección {{ $t->section->name }}</span>
                        @else
                            <span class="text-white/50 text-sm">Sin butaca asignada</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        @if($reservation->event->qr_image_path)
            <div class="rounded-2xl bg-black/40 border border-red-900/30 p-6 text-center">
                <p class="text-sm font-semibold text-white/80 mb-4">Realice el pago escaneando el código QR</p>
                <img src="{{ asset('storage/'.$reservation->event->qr_image_path) }}" alt="QR de pago" class="mx-auto max-w-[220px] h-auto rounded-xl border border-red-900/50">
            </div>
        @else
            <div class="rounded-2xl border border-red-900/50 bg-red-900/10 p-6">
                <p class="text-white/80 font-medium">Realice el pago por el medio indicado por el organizador (transferencia, efectivo, etc.). Luego suba la captura o foto del comprobante abajo.</p>
            </div>
        @endif

        <p class="text-white/70 text-sm leading-relaxed">
            Después de pagar, <strong class="text-white">suba una captura o foto del comprobante de pago</strong> (transferencia, depósito, etc.). Revisaremos el comprobante y te enviaremos los tickets por correo una vez autorizado.
        </p>

        <form method="POST" action="{{ route('checkout.confirm', $reservation) }}" enctype="multipart/form-data" class="space-y-5 pt-2">
            @csrf

            <div class="p-4 rounded-xl border border-red-900/50">
                <label for="payment_receipt" class="block font-semibold text-white/80 mb-2">Comprobante de pago (captura o foto) <span class="text-red-400">*</span></label>
                <input id="payment_receipt" type="file" name="payment_receipt" accept="image/*" required
                       class="w-full rounded-xl border border-red-900/50 bg-black/60 px-4 py-3 text-white file:mr-4 file:rounded-lg file:border-0 file:bg-[#e50914] file:px-4 file:py-2 file:text-white file:font-medium @error('payment_receipt') border-red-500 @enderror">
                <p class="mt-1 text-xs text-white/50">Formatos: JPG, PNG, etc. Máximo 5 MB.</p>
                @error('payment_receipt')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-red-900/50 hover:border-[#e50914]/50 transition cursor-pointer">
                <input type="checkbox" name="accept_terms" value="1" required class="mt-1 rounded border-red-900/50 text-[#e50914] focus:ring-[#e50914] bg-black/60 size-5">
                <span class="text-white/80">Acepto los <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="text-[#e50914] hover:text-red-400 underline underline-offset-2">términos y condiciones</a>.</span>
            </label>
            @error('accept_terms')<p class="text-sm text-red-400">{{ $message }}</p>@enderror

            <button type="submit" class="w-full rounded-2xl bg-[#e50914] px-6 py-4 text-white font-bold text-lg hover:bg-red-600 transition">
                Enviar comprobante y finalizar reserva
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
                const now = Date.now();
                const left = Math.max(0, Math.floor((expiresAtMs - now) / 1000));
                const m = Math.floor(left / 60);
                const s = left % 60;
                self.display = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                if (left <= 0) {
                    clearInterval(self.interval);
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("reservations.cancel", $reservation) }}';
                    const csrf = document.createElement('input');
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            tick();
            this.interval = setInterval(tick, 1000);
        }
    };
}
</script>
@endpush
@endsection
