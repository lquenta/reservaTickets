@extends('layouts.admin')

@section('title', 'Diseño de ticket - ' . $event->name)

@section('admin')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Diseño de ticket</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">{{ $event->name }} — Haz clic en cualquier texto marcado para editarlo (WYSIWYG).</p>
</div>

<form method="POST" action="{{ route('admin.ticket-templates.update', $event) }}" class="max-w-2xl"
      x-data="ticketWysiwyg({
        title: {{ json_encode(old('design_title', $template->design['title'] ?? 'Entrada')) }},
        subtitle: {{ json_encode(old('design_subtitle', $template->design['subtitle'] ?? '')) }},
        priceLabel: {{ json_encode(old('design_price_label', $template->design['price_label'] ?? 'Precio')) }},
        seatLabel: {{ json_encode(old('design_seat_label', $template->design['seat_label'] ?? 'Butaca')) }},
        price: {{ json_encode(old('price', $template->price ?? 0) !== '' ? old('price', $template->price ?? 0) : '0') }},
        eventName: {{ json_encode($event->name) }},
        eventDate: {{ json_encode($event->starts_at->translatedFormat('l d F Y, H:i')) }},
        eventVenue: {{ json_encode($event->venue) }}
      })">
    @csrf
    @method('PUT')

    {{-- Hidden inputs para envío del formulario --}}
    <input type="hidden" name="design_title" :value="title">
    <input type="hidden" name="design_subtitle" :value="subtitle">
    <input type="hidden" name="design_price_label" :value="priceLabel">
    <input type="hidden" name="design_seat_label" :value="seatLabel">
    <input type="hidden" name="price" :value="price">

    {{-- Ticket WYSIWYG: el ticket es el editor --}}
    <div class="rounded-3xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-gradient-to-br from-slate-50 to-violet-50/30 dark:from-slate-800 dark:to-violet-950/20 p-6 md:p-8 shadow-xl mb-8">
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 flex items-center gap-2">
            <span aria-hidden="true">✏️</span> Edita haciendo clic en los textos resaltados
        </p>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border-2 border-violet-400 dark:border-violet-500 p-6 md:p-8 shadow-lg max-w-md mx-auto text-left">
            {{-- Título (editable) --}}
            <div class="border-b border-violet-200 dark:border-violet-700 pb-3 mb-2">
                <span x-show="!editingTitle"
                      @click="startEdit('title')"
                      class="block text-lg font-bold text-violet-700 dark:text-violet-400 cursor-text rounded px-1 -mx-1 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition min-h-[1.5rem]"
                      x-text="title || 'Entrada'"></span>
                <input x-show="editingTitle"
                       x-ref="titleInput"
                       x-model="title"
                       @blur="editingTitle = false"
                       @keydown.enter.prevent="editingTitle = false; $refs.subtitleInput?.focus()"
                       type="text" maxlength="255"
                       class="block w-full text-lg font-bold text-violet-700 dark:text-violet-400 bg-transparent border-0 border-b-2 border-violet-400 rounded-none px-0 py-0 focus:ring-0 focus:border-violet-500">
            </div>
            {{-- Subtítulo (editable) --}}
            <div class="mb-3 min-h-[1.5rem]">
                <span x-show="!editingSubtitle"
                      @click="startEdit('subtitle')"
                      class="block text-xs text-slate-500 dark:text-slate-400 cursor-text rounded px-1 -mx-1 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition"
                      x-text="subtitle ? subtitle : 'Haz clic para añadir subtítulo'"></span>
                <input x-show="editingSubtitle"
                       x-ref="subtitleInput"
                       x-model="subtitle"
                       @blur="editingSubtitle = false"
                       @keydown.enter.prevent="editingSubtitle = false; $refs.seatLabelInput?.focus()"
                       type="text" maxlength="500"
                       class="block w-full text-xs text-slate-500 dark:text-slate-400 bg-transparent border-0 border-b border-slate-300 rounded-none px-0 py-0 focus:ring-0">
            </div>
            {{-- Evento (solo lectura) --}}
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 m-0" x-text="eventName"></p>
            <p class="text-xs text-slate-600 dark:text-slate-300 m-0 mb-3" x-text="eventDate + ' · ' + eventVenue"></p>
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 m-0">Titular: <span class="font-normal text-slate-600 dark:text-slate-300">Nombre del titular</span></p>
            {{-- Asiento: placeholder no editable + título editable --}}
            <div class="mb-3 py-2 px-2 rounded bg-slate-100 dark:bg-slate-800/60 border border-dashed border-slate-300 dark:border-slate-600">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1.5">En el ticket real aquí se mostrará la butaca elegida.</p>
                <div class="flex items-baseline gap-2 flex-wrap">
                    <span x-show="!editingSeatLabel"
                          @click="startEdit('seatLabel')"
                          class="cursor-text rounded px-1 -mx-1 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition font-bold text-slate-800 dark:text-slate-200"
                          x-text="seatLabel || 'Butaca'"></span>
                    <input x-show="editingSeatLabel"
                           x-ref="seatLabelInput"
                           x-model="seatLabel"
                           @blur="editingSeatLabel = false"
                           @keydown.enter.prevent="editingSeatLabel = false"
                           type="text" maxlength="100"
                           class="w-24 text-sm font-bold bg-transparent border-0 border-b-2 border-slate-300 rounded-none px-0 py-0 focus:ring-0 focus:border-violet-500">
                    <span class="text-slate-600 dark:text-slate-400">:</span>
                    <span class="font-mono text-violet-600 dark:text-violet-400 select-none" title="Placeholder (no editable)">A-1</span>
                </div>
            </div>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-3 text-center">Evento y titular son de ejemplo. En el ticket real se rellenan con los datos de cada reserva. El PDF y el correo muestran el mismo diseño (titular, butaca y QR).</p>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-6 py-3 text-white font-bold shadow-lg shadow-violet-500/30 hover:shadow-violet-500/50 transition">Guardar diseño</button>
        <a href="{{ route('admin.events.index') }}" class="rounded-xl border-2 border-slate-300 dark:border-slate-600 px-6 py-3 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition">Volver a eventos</a>
    </div>
</form>

@push('scripts')
<script>
function ticketWysiwyg(initial) {
    return {
        title: initial.title || 'Entrada',
        subtitle: initial.subtitle || '',
        priceLabel: initial.priceLabel || 'Precio',
        seatLabel: initial.seatLabel || 'Butaca',
        price: String(initial.price ?? '0'),
        eventName: initial.eventName || '',
        eventDate: initial.eventDate || '',
        eventVenue: initial.eventVenue || '',
        editingTitle: false,
        editingSubtitle: false,
        editingPriceLabel: false,
        editingSeatLabel: false,
        editingPrice: false,
        startEdit(field) {
            this.editingTitle = false;
            this.editingSubtitle = false;
            this.editingPriceLabel = false;
            this.editingSeatLabel = false;
            this.editingPrice = false;
            if (field === 'title') { this.editingTitle = true; this.$nextTick(() => this.$refs.titleInput?.focus()); }
            if (field === 'subtitle') { this.editingSubtitle = true; this.$nextTick(() => this.$refs.subtitleInput?.focus()); }
            if (field === 'priceLabel') { this.editingPriceLabel = true; this.$nextTick(() => this.$refs.priceLabelInput?.focus()); }
            if (field === 'seatLabel') { this.editingSeatLabel = true; this.$nextTick(() => this.$refs.seatLabelInput?.focus()); }
            if (field === 'price') { this.editingPrice = true; this.$nextTick(() => this.$refs.priceInput?.focus()); }
        },
        normalizePrice() {
            const n = parseFloat(this.price);
            this.price = (isNaN(n) || n < 0) ? '0' : String(n);
        },
        formatPrice(val) {
            const n = parseFloat(val);
            if (isNaN(n) || n < 0) return '0.00';
            return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
    };
}
</script>
@endpush
@endsection
