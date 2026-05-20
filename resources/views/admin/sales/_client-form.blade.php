@php
    $lookupUrl = $lookupUrl ?? null;
    $submitRoute = $submitRoute;
    $title = $title ?? 'Datos del cliente';
    $submitLabel = $submitLabel ?? 'Continuar — elegir butacas';
@endphp
@php
    $backUrl = isset($flow) ? route($flow->eventsIndexRoute) : route('admin.events.index');
    $isSellerLayout = isset($flow) && $flow->layout === 'layouts.app';
@endphp
<div class="mb-8">
    <a href="{{ $backUrl }}" class="text-sm {{ $isSellerLayout ? 'text-[#e50914] hover:text-red-400' : 'text-violet-600 dark:text-violet-400 hover:underline' }}">← Eventos</a>
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white mt-2">{{ $title }}</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">{{ $event->name }} · {{ $event->starts_at->translatedFormat('d/m/Y H:i') }}</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 md:p-8 max-w-xl"
     x-data="adminClientLookup(@json($lookupUrl), {{ json_encode(['name' => old('client_name'), 'email' => old('client_email'), 'phone' => old('client_phone')]) }})">
    <form method="POST" action="{{ $submitRoute }}" class="space-y-4">
        @csrf
        <div>
            <label for="client_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Correo electrónico</label>
            <input type="email" name="client_email" id="client_email" x-model="email" @blur="lookup()" required
                   class="w-full rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white">
            @error('client_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="client_email_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirmar correo</label>
            <input type="email" name="client_email_confirmation" id="client_email_confirmation" required
                   class="w-full rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white">
            @error('client_email_confirmation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div x-show="existing" x-cloak class="rounded-xl bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-700 p-4 text-sm text-amber-900 dark:text-amber-200">
            <p class="font-semibold">Cliente existente</p>
            <p class="mt-1">Se usará la cuenta registrada. Los datos se muestran en solo lectura salvo que marques actualizar.</p>
        </div>
        <div>
            <label for="client_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre completo</label>
            <input type="text" name="client_name" id="client_name" x-model="name" :readonly="existing && !updateProfile" required
                   class="w-full rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white">
            @error('client_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="client_phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Teléfono</label>
            <input type="text" name="client_phone" id="client_phone" x-model="phone" :readonly="existing && !updateProfile" required
                   class="w-full rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white">
            @error('client_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div x-show="existing" x-cloak>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="update_existing_profile" value="1" x-model="updateProfile" class="rounded border-slate-400">
                Actualizar nombre y teléfono en la cuenta
            </label>
        </div>
        <button type="submit" class="w-full rounded-xl bg-violet-600 px-5 py-3 text-white font-semibold hover:bg-violet-700 transition">{{ $submitLabel }}</button>
    </form>
</div>

@push('scripts')
<script>
function adminClientLookup(lookupUrl, oldData) {
    return {
        email: oldData.email || '',
        name: oldData.name || '',
        phone: oldData.phone || '',
        existing: false,
        updateProfile: false,
        async lookup() {
            if (!lookupUrl || !this.email) return;
            try {
                const fd = new FormData();
                fd.append('email', this.email);
                fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                const res = await fetch(lookupUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                const data = await res.json();
                if (data.exists) {
                    this.existing = true;
                    this.name = data.name;
                    this.phone = data.phone;
                    this.updateProfile = false;
                } else {
                    this.existing = false;
                }
            } catch (e) {}
        },
    };
}
</script>
@endpush
