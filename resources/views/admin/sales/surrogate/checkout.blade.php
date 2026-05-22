@extends($flow->layout ?? 'layouts.admin')

@section('title', 'Checkout venta surrogada')

@section($flow->contentSection ?? 'admin')
@php $isSellerLayout = ($flow->layout ?? '') === 'layouts.app'; @endphp
@if($isSellerLayout)
    @include('seller.surrogate.checkout', compact('reservation', 'totalPrice', 'flow', 'checkoutMap'))
@else
<div class="max-w-2xl mx-auto mb-6">
    <a href="{{ route($flow->checkoutSuccessRoute) }}" class="text-sm {{ $isSellerLayout ? 'text-[#e50914] hover:text-red-400' : 'text-violet-600 dark:text-violet-400 hover:underline' }}">← Volver</a>
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mt-2">Checkout — venta surrogada</h1>
    <p class="text-slate-600 dark:text-slate-400">{{ $reservation->event->name }}</p>
</div>

@php $client = $reservation->user; @endphp
<div class="max-w-2xl mx-auto rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 md:p-8 space-y-6">
    <div class="rounded-xl bg-slate-100 dark:bg-slate-700/50 p-4">
        <p class="font-semibold text-slate-800 dark:text-white">Cliente: {{ $client->name }}</p>
        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $client->email }} · {{ $client->phone }}</p>
        @if($client->hasVerifiedEmail())
            <p class="text-sm text-emerald-600 dark:text-emerald-400 mt-1">Correo verificado — los tickets se enviarán a {{ $client->email }} cuando un administrador autorice el pago.</p>
        @else
            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1 font-medium">Correo sin verificar — tras la autorización se intentará enviar a {{ $client->email }}; si no llega, debes entregar los tickets al cliente.</p>
        @endif
        <p class="text-sm text-violet-700 dark:text-violet-300 mt-2">Vendido por: {{ $reservation->soldBy?->name }}</p>
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-600 p-4">
        <p class="font-semibold mb-2">Entradas ({{ $reservation->reservationTickets->count() }})</p>
        @if(isset($totalPrice) && $totalPrice > 0)
            <p class="text-slate-700 dark:text-slate-300 mb-3">Total: <strong>{{ number_format($totalPrice, 2, ',', '.') }} Bs</strong></p>
        @endif
        <ul class="text-sm space-y-1 text-slate-600 dark:text-slate-400">
            @foreach($reservation->reservationTickets as $t)
                <li>{{ $t->holder_name }}
                    @if($t->seat) — {{ $t->seat->display_label }}@endif
                    @if($t->section) — {{ $t->section->name }}@endif
                </li>
            @endforeach
        </ul>
    </div>

    @if($reservation->event->qr_image_path)
        <div class="text-center rounded-xl bg-slate-50 dark:bg-slate-900/50 p-4">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">QR de pago del evento</p>
            <img src="{{ asset('storage/'.$reservation->event->qr_image_path) }}" alt="QR" class="mx-auto max-w-[200px] rounded-lg">
        </div>
    @endif

    <form method="POST" action="{{ $flow->route('surrogate-sale.checkout.confirm', $reservation) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label for="payment_receipt" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Comprobante de pago (imagen)</label>
            <input type="file" name="payment_receipt" id="payment_receipt" accept="image/*" required
                   class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-violet-600 file:text-white">
            @error('payment_receipt')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <label class="inline-flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300">
            <input type="checkbox" name="accept_terms" value="1" required class="mt-1 rounded">
            <span>Acepto los términos y condiciones de la plataforma.</span>
        </label>
        @error('accept_terms')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

        @if(!$client->hasVerifiedEmail())
        <div class="rounded-xl border-2 border-amber-400 bg-amber-50 dark:bg-amber-900/20 p-4">
            <label class="inline-flex items-start gap-2 text-sm text-amber-900 dark:text-amber-100">
                <input type="checkbox" name="seller_delivery_responsibility" value="1" required class="mt-1 rounded">
                <span>Asumo la responsabilidad de que el cliente reciba sus tickets (correo, WhatsApp u otro medio) tras la autorización si el envío por correo no llega o el correo no está verificado.</span>
            </label>
            @error('seller_delivery_responsibility')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        @endif

        <button type="submit" class="w-full rounded-xl bg-violet-600 px-5 py-3 text-white font-semibold hover:bg-violet-700 transition">
            Confirmar y enviar a revisión
        </button>
    </form>
</div>
@endif
@endsection
