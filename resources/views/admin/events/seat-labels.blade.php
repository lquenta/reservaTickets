@extends('layouts.admin')

@section('title', 'Etiquetas de asientos - ' . $event->name)

@section('admin')
<nav class="mb-6 text-sm text-slate-600 dark:text-slate-400">
    <a href="{{ route('admin.events.show', $event) }}" class="hover:text-violet-600 dark:hover:text-violet-400">← {{ $event->name }}</a>
</nav>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Etiquetas de asientos</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-2 max-w-3xl">
        PDF con una etiqueta por butaca (ocupadas y libres). Elegí cuántas van por hoja; el recuento de páginas se actualiza al instante.
        El código de asiento llena la etiqueta; el color del sector va de fondo suave.
    </p>
</div>

<div class="grid lg:grid-cols-[minmax(0,22rem)_1fr] gap-6 items-start"
     x-data="seatLabelPreview(@js($payload))">

    <div class="space-y-5">
        <section class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-5 space-y-4">
            <h2 class="font-semibold text-slate-800 dark:text-white">Distribución</h2>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Etiquetas por hoja</label>
                <div class="flex flex-wrap gap-2">
                    <template x-for="n in perPageOptions" :key="n">
                        <button type="button"
                                @click="perPage = n"
                                class="min-w-[3rem] rounded-xl px-3 py-2 text-sm font-semibold border-2 transition"
                                :class="perPage === n
                                    ? 'border-violet-600 bg-violet-600 text-white'
                                    : 'border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:border-violet-400'">
                            <span x-text="n"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Orientación</label>
                <select x-model="orientation" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 px-3 py-2">
                    <option value="portrait">Vertical (A4)</option>
                    <option value="landscape">Horizontal (A4)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sector</label>
                <select x-model="sectionId" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 px-3 py-2">
                    <option value="">Todos</option>
                    <template x-for="sec in sections" :key="sec.id">
                        <option :value="String(sec.id)" x-text="sec.name + ' (' + sec.count + ')'"></option>
                    </template>
                </select>
            </div>
        </section>

        <section class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-5 space-y-4">
            <h2 class="font-semibold text-slate-800 dark:text-white">Color e intensidad</h2>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Origen del color</label>
                <select x-model="colorMode" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 px-3 py-2">
                    <option value="sector">Color de cada sector</option>
                    <option value="custom">Un color para todas</option>
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Intensidad del fondo</label>
                    <span class="text-sm font-semibold text-violet-600 dark:text-violet-400" x-text="opacity + '%'"></span>
                </div>
                <input type="range" min="0" max="80" step="1" x-model.number="opacity"
                       class="w-full accent-violet-600">
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Recomendado 20%: tinte suave del sector, sin tapar el código.</p>
            </div>

            <div x-show="colorMode === 'custom'">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Color único</label>
                <div class="flex items-center gap-3">
                    <input type="color" x-model="customColor" class="h-10 w-14 cursor-pointer rounded border border-slate-300 dark:border-slate-600 bg-transparent p-0">
                    <span class="font-mono text-sm text-slate-600 dark:text-slate-300" x-text="customColor"></span>
                </div>
            </div>

            <div x-show="colorMode === 'sector'" class="space-y-3">
                <p class="text-xs text-slate-500 dark:text-slate-400">Podés ajustar el color de cada sector solo para este PDF (no cambia el mapa del lugar).</p>
                <template x-for="sec in sections" :key="'c-' + sec.id">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-slate-700 dark:text-slate-200 truncate" x-text="sec.name"></span>
                        <input type="color"
                               :value="sectionColors[sec.id] || sec.color"
                               @input="sectionColors[sec.id] = $event.target.value"
                               class="h-9 w-12 cursor-pointer rounded border border-slate-300 dark:border-slate-600 bg-transparent p-0">
                    </div>
                </template>
            </div>
        </section>

        <a :href="pdfUrl"
           target="_blank"
           rel="noopener"
           class="flex items-center justify-center gap-2 w-full rounded-xl bg-gradient-to-r from-[#4B0082] to-[#FF2DAA] px-5 py-3 text-white font-bold shadow-lg shadow-violet-500/30 hover:shadow-violet-500/50 transition">
            <x-icon name="pdf" class="w-5 h-5" />
            Generar PDF
        </a>
        <a :href="pdfUrl + '&download=1'"
           class="flex items-center justify-center gap-2 w-full rounded-xl border-2 border-violet-500 px-5 py-3 text-violet-700 dark:text-violet-300 font-semibold hover:bg-violet-50 dark:hover:bg-violet-900/30 transition">
            <x-icon name="download" class="w-4 h-4" />
            Descargar
        </a>
    </div>

    <div class="space-y-5">
        <div class="grid sm:grid-cols-3 gap-3">
            <div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-4">
                <p class="text-xs font-semibold uppercase text-violet-600 dark:text-violet-400">Etiquetas</p>
                <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" x-text="filteredLabels.length"></p>
            </div>
            <div class="rounded-2xl border-2 border-emerald-200/60 dark:border-emerald-700/50 bg-white dark:bg-slate-800/80 p-4">
                <p class="text-xs font-semibold uppercase text-emerald-600 dark:text-emerald-400">Hojas</p>
                <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" x-text="pageCount"></p>
                <p class="text-xs text-slate-500 mt-1">
                    <span x-text="grid.cols"></span> × <span x-text="grid.rows"></span>
                    · <span x-text="perPage"></span> por hoja
                </p>
            </div>
            <div class="rounded-2xl border-2 border-slate-200/60 dark:border-slate-600 bg-white dark:bg-slate-800/80 p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Ocupadas / libres</p>
                <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">
                    <span x-text="occupiedCount"></span><span class="text-lg text-slate-500">/<span x-text="filteredLabels.length - occupiedCount"></span></span>
                </p>
            </div>
        </div>

        <section class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-5">
            <h2 class="font-semibold text-slate-800 dark:text-white mb-1">Muestra de etiqueta</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">El código ocupa casi toda la celda; el color del sector queda de fondo.</p>
            <div class="max-w-sm mx-auto">
                <template x-if="sampleLabel">
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-600 aspect-[4/3] flex flex-col items-center justify-center text-center px-2 py-3"
                         :style="sampleStyle(sampleLabel)">
                        <p class="relative z-10 text-xs font-semibold uppercase tracking-wide text-slate-700" x-text="sampleLabel.section || ''"></p>
                        <span class="relative z-10 font-mono font-black leading-none text-violet-800"
                              :style="'font-size:' + sampleSeatFont + 'px'"
                              x-text="sampleLabel.label"></span>
                    </div>
                </template>
            </div>
        </section>

        <section class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-5">
            <div class="flex flex-wrap items-end justify-between gap-2 mb-4">
                <div>
                    <h2 class="font-semibold text-slate-800 dark:text-white">Vista previa de hojas</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Primera hoja. El PDF incluye todas.</p>
                </div>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
                    Hoja 1 de <span x-text="pageCount || 0"></span>
                </p>
            </div>
            <div class="mx-auto bg-slate-100 dark:bg-slate-900 rounded-xl p-3 overflow-auto">
                <div class="mx-auto bg-white shadow-md origin-top"
                     :style="sheetStyle">
                    <div class="grid h-full w-full" :style="sheetGridStyle">
                        <template x-for="(cell, idx) in previewCells" :key="idx">
                            <div class="relative overflow-hidden border border-dashed border-slate-300 flex items-center justify-center text-center p-1"
                                 :style="cell ? sampleStyle(cell) : {}">
                                <template x-if="cell">
                                    <div class="relative z-10 min-w-0 w-full h-full flex flex-col items-center justify-center px-0.5">
                                        <p class="font-semibold uppercase leading-tight text-slate-700 truncate w-full"
                                           :style="'font-size:' + previewFont.section + 'px'"
                                           x-text="cell.section || ''"></p>
                                        <p class="font-mono font-black text-violet-800 leading-none"
                                           :style="'font-size:' + previewFont.seat + 'px'"
                                           x-text="cell.label"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
function seatLabelPreview(payload) {
    const pdfBase = @js(route('admin.events.seat-labels.pdf', $event));
    const sectionColors = {};
    (payload.sections || []).forEach((sec) => {
        sectionColors[sec.id] = sec.color;
    });
    return {
        eventName: payload.event_name,
        eventDate: payload.event_date,
        venueName: payload.venue_name,
        labels: payload.labels || [],
        sections: payload.sections || [],
        perPageOptions: payload.defaults?.per_page_options || [1, 2, 4, 8, 16, 32, 64],
        perPage: payload.defaults?.per_page || 8,
        opacity: payload.defaults?.opacity ?? 20,
        orientation: 'portrait',
        sectionId: '',
        colorMode: 'sector',
        customColor: '#2563eb',
        sectionColors,
        get filteredLabels() {
            if (this.sectionId === '' || this.sectionId === null) return this.labels;
            const id = Number(this.sectionId);
            return this.labels.filter((l) => Number(l.section_id ?? 0) === id);
        },
        get occupiedCount() {
            return this.filteredLabels.filter((l) => l.occupied).length;
        },
        get pageCount() {
            const n = this.filteredLabels.length;
            if (n <= 0) return 0;
            return Math.ceil(n / this.perPage);
        },
        get grid() {
            const perPage = this.perPage;
            const target = this.orientation === 'landscape' ? 297 / 210 : 210 / 297;
            let bestCols = 1, bestRows = perPage, bestScore = Infinity;
            for (let cols = 1; cols <= perPage; cols++) {
                if (perPage % cols !== 0) continue;
                const rows = perPage / cols;
                const score = Math.abs((cols / rows) - target);
                if (score < bestScore) {
                    bestScore = score;
                    bestCols = cols;
                    bestRows = rows;
                }
            }
            return { cols: bestCols, rows: bestRows };
        },
        get previewFont() {
            const root = Math.sqrt(this.perPage);
            return {
                seat: Math.max(18, Math.min(96, Math.round(120 / root))),
                section: Math.max(6, Math.min(11, Math.round(14 / root))),
            };
        },
        get sampleSeatFont() {
            const label = this.sampleLabel?.label || 'A1';
            const chars = Math.max(2, String(label).length);
            return Math.max(48, Math.min(120, Math.round(220 / chars)));
        },
        get sheetStyle() {
            const portrait = this.orientation !== 'landscape';
            const w = portrait ? 210 : 297;
            const h = portrait ? 297 : 210;
            const maxW = 520;
            const scale = maxW / w;
            return `width:${w * scale}px;height:${h * scale}px;`;
        },
        get sheetGridStyle() {
            return `grid-template-columns:repeat(${this.grid.cols},minmax(0,1fr));grid-template-rows:repeat(${this.grid.rows},minmax(0,1fr));`;
        },
        get previewCells() {
            const cells = this.filteredLabels.slice(0, this.perPage);
            while (cells.length < this.perPage) cells.push(null);
            return cells;
        },
        get sampleLabel() {
            return this.filteredLabels[0] || null;
        },
        overlayColor(label) {
            if (this.colorMode === 'custom') return this.customColor;
            const sid = label.section_id ?? 0;
            return this.sectionColors[sid] || label.color || '#64748b';
        },
        hexToRgb(hex) {
            const h = String(hex || '').replace('#', '');
            if (h.length !== 6) return null;
            return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16)];
        },
        sampleStyle(label) {
            const rgb = this.hexToRgb(this.overlayColor(label));
            const a = Math.max(0, Math.min(80, Number(this.opacity))) / 100;
            if (!rgb) return 'background-color:#ffffff;';
            const r = Math.round(255 * (1 - a) + rgb[0] * a);
            const g = Math.round(255 * (1 - a) + rgb[1] * a);
            const b = Math.round(255 * (1 - a) + rgb[2] * a);
            return `background-color:rgb(${r},${g},${b});`;
        },
        get pdfUrl() {
            const params = new URLSearchParams();
            params.set('per_page', String(this.perPage));
            params.set('orientation', this.orientation);
            params.set('color_mode', this.colorMode);
            params.set('custom_color', this.customColor);
            params.set('overlay_opacity', String(this.opacity));
            if (this.sectionId !== '' && this.sectionId !== null) {
                params.set('section_id', String(this.sectionId));
            }
            Object.keys(this.sectionColors).forEach((id) => {
                params.set(`section_colors[${id}]`, this.sectionColors[id]);
            });
            return pdfBase + '?' + params.toString();
        },
    };
}
</script>
@endpush
