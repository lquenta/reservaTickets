@php
    $map = $checkoutMap ?? null;
@endphp
@if($map && !empty($map['layoutElements']))
<script>
    if (typeof window.computeLayoutContentBoundsFromElements !== 'function') {
        (function () {
            const LAYOUT_MAP_CONTENT_PAD = 16;
            function computeLayoutContentBoundsFromElements(els) {
                if (!Array.isArray(els) || !els.length) return null;
                let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
                for (let i = 0; i < els.length; i++) {
                    const el = els[i];
                    const x = Number(el.x) || 0;
                    const y = Number(el.y) || 0;
                    const w = Math.max(8, Number(el.w) || 48);
                    const h = Math.max(8, Number(el.h) || 48);
                    if (x < minX) minX = x;
                    if (y < minY) minY = y;
                    if (x + w > maxX) maxX = x + w;
                    if (y + h > maxY) maxY = y + h;
                }
                if (!Number.isFinite(minX) || !Number.isFinite(minY)) return null;
                return { minX, minY, maxX, maxY };
            }
            window.LAYOUT_MAP_CONTENT_PAD = LAYOUT_MAP_CONTENT_PAD;
            window.computeLayoutContentBoundsFromElements = computeLayoutContentBoundsFromElements;
            window.LAYOUT_CHECKOUT_ZOOM_PAD = 16;
            window.LAYOUT_CHECKOUT_MIN_SCALE = 0.12;
            window.layoutCheckoutFitScale = function (el, dw, dh, pad) {
                if (!el || dw <= 0 || dh <= 0) return 1;
                const p = pad != null ? pad : window.LAYOUT_CHECKOUT_ZOOM_PAD;
                const cw = Math.max(1, el.clientWidth - p);
                const ch = Math.max(1, el.clientHeight - p);
                let fit = Math.min(1, cw / dw, ch / dh);
                if (!Number.isFinite(fit) || fit <= 0) fit = 1;
                return Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, Math.min(1, fit));
            };
        })();
    }
</script>
<div class="rounded-2xl border border-red-900/50 bg-black/60 backdrop-blur px-4 py-5 sm:p-6 mb-8"
     x-data="checkoutLayoutMapReadonly(@js($map))">
    <h2 class="font-display text-lg font-bold text-[#e50914] tracking-wider mb-1 text-center">Plano — butacas elegidas</h2>
    <p class="text-white/60 text-xs text-center mb-4">Resaltadas en rojo las butacas de esta reserva.</p>
    <div class="mb-3 flex flex-wrap items-center justify-center gap-2 rounded-lg border border-red-900/50 bg-black/50 px-2 py-2 text-white shadow-inner">
        <span class="w-11 text-center font-mono text-xs tabular-nums text-white/90" x-text="layoutZoomPercent() + '%'"></span>
        <button type="button" class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-2 text-lg font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomOut()" title="Alejar plano" aria-label="Alejar plano">−</button>
        <button type="button" class="inline-flex h-9 items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-3 text-xs font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomResetFit()" title="Encajar al espacio" aria-label="Encajar plano">Encajar</button>
        <button type="button" class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-2 text-lg font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomIn()" title="Acercar plano" aria-label="Acercar plano">+</button>
        <span class="hidden text-[10px] text-white/45 sm:inline">Ctrl + rueda</span>
    </div>
    <div x-ref="layoutViewport"
         @resize.window="recalcLayoutViewportScale()"
         class="relative w-full min-h-[180px] max-h-[min(60dvh,520px)] touch-manipulation overflow-auto overscroll-contain rounded-xl border border-red-900/40 bg-[radial-gradient(circle,_rgba(255,255,255,0.12)_1px,_transparent_1px)] bg-[size:16px_16px] p-2">
        <div class="relative mx-auto shrink-0" :style="layoutScaledHostStyle">
            <div class="relative" :style="layoutScaledStageStyle">
                <template x-for="el in sortedLayoutElements" :key="el.id">
                    <div class="absolute isolate" :style="layoutElementWrapperStyle(el) + 'pointer-events:none;'">
                        <template x-if="layoutElType(el) === 'seat'">
                            <div class="absolute inset-0 z-10 rounded-md text-[9px] sm:text-[10px] font-bold px-0.5 sm:px-1 border-2 flex items-center justify-center leading-none overflow-hidden text-center pointer-events-none"
                                 :style="layoutSeatFaceStyle(el)">
                                <span class="truncate max-w-full" x-text="el.seat ? el.seat.label : ''"></span>
                            </div>
                        </template>
                        <template x-if="layoutElType(el) === 'stage'">
                            <div class="absolute inset-0 z-0 flex items-center justify-center rounded-md border border-red-500/40 bg-red-700 px-0.5 text-white shadow-md pointer-events-none overflow-hidden">
                                <span class="max-h-full overflow-hidden text-center text-[8px] font-semibold uppercase leading-tight sm:text-[10px]" x-text="(el.meta && el.meta.label) ? el.meta.label : 'ESCENARIO'"></span>
                            </div>
                        </template>
                        <template x-if="layoutElType(el) === 'speaker'">
                            <div class="absolute inset-0 z-0 flex items-center justify-center rounded-md border border-amber-400/40 bg-amber-600 px-0.5 text-white shadow-md pointer-events-none overflow-hidden">
                                <span class="max-h-full overflow-hidden text-center text-[8px] font-semibold uppercase leading-tight sm:text-[10px]" x-text="(el.meta && el.meta.label) ? el.meta.label : 'PARLANTE'"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function checkoutLayoutMapReadonly(config) {
    const selectedSet = new Set((config.selectedSeatIds || []).map(id => parseInt(id, 10)));
    const lc = config.layoutCanvas || {};
    const layoutCanvasW = lc.width != null && Number(lc.width) > 0 ? Number(lc.width) : null;
    const layoutCanvasH = lc.height != null && Number(lc.height) > 0 ? Number(lc.height) : null;
    return {
        layoutElements: Array.isArray(config.layoutElements) ? config.layoutElements : [],
        sectionPalettesById: config.sectionPalettesById || {},
        _layoutViewportScale: 1,
        _layoutViewportUserScale: null,
        _layoutViewportRo: null,
        _layoutCheckoutWheelBound: false,
        init() {
            this.$nextTick(() => this.setupLayoutViewportObserver());
        },
        setupLayoutViewportObserver() {
            const el = this.$refs.layoutViewport;
            if (!el || this._layoutViewportRo) return;
            const self = this;
            this._layoutViewportRo = new ResizeObserver(() => this.recalcLayoutViewportScale());
            this._layoutViewportRo.observe(el);
            this.recalcLayoutViewportScale();
            if (!this._layoutCheckoutWheelBound) {
                this._layoutCheckoutWheelBound = true;
                el.addEventListener('wheel', function(e) {
                    if (!(e.ctrlKey || e.metaKey)) return;
                    e.preventDefault();
                    if (e.deltaY > 0) self.layoutZoomOut(); else self.layoutZoomIn();
                }, { passive: false });
            }
        },
        recalcLayoutViewportScale() {
            const el = this.$refs.layoutViewport;
            if (!el) return;
            const fit = window.layoutCheckoutFitScale(el, this.layoutDesignWidth, this.layoutDesignHeight, window.LAYOUT_CHECKOUT_ZOOM_PAD);
            const u = this._layoutViewportUserScale;
            this._layoutViewportScale = (u == null || !Number.isFinite(Number(u)))
                ? fit
                : Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, Math.min(Number(u), 1));
        },
        layoutZoomPercent() { return Math.round((Number(this._layoutViewportScale) || 1) * 100); },
        layoutZoomOut() {
            const cur = this._layoutViewportUserScale != null ? Number(this._layoutViewportUserScale) : Number(this._layoutViewportScale);
            this._layoutViewportUserScale = Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, cur / 1.2);
            this.recalcLayoutViewportScale();
        },
        layoutZoomIn() {
            const cur = this._layoutViewportUserScale != null ? Number(this._layoutViewportUserScale) : Number(this._layoutViewportScale);
            this._layoutViewportUserScale = Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, Math.min(1, cur * 1.2));
            this.recalcLayoutViewportScale();
        },
        layoutZoomResetFit() {
            this._layoutViewportUserScale = null;
            const vp = this.$refs.layoutViewport;
            if (vp) { vp.scrollLeft = 0; vp.scrollTop = 0; }
            this.recalcLayoutViewportScale();
        },
        get layoutDesignWidth() {
            const b = window.computeLayoutContentBoundsFromElements(this.layoutElements);
            if (b) return Math.max(200, Math.ceil(b.maxX - b.minX + 2 * window.LAYOUT_MAP_CONTENT_PAD));
            if (layoutCanvasW != null) return layoutCanvasW;
            return 960;
        },
        get layoutDesignHeight() {
            const b = window.computeLayoutContentBoundsFromElements(this.layoutElements);
            if (b) return Math.max(200, Math.ceil(b.maxY - b.minY + 2 * window.LAYOUT_MAP_CONTENT_PAD));
            if (layoutCanvasH != null) return layoutCanvasH;
            return 640;
        },
        get layoutScaledHostStyle() {
            const s = this._layoutViewportScale;
            return 'width:' + (this.layoutDesignWidth * s) + 'px;height:' + (this.layoutDesignHeight * s) + 'px;';
        },
        get layoutScaledStageStyle() {
            const s = this._layoutViewportScale;
            const dw = this.layoutDesignWidth;
            const dh = this.layoutDesignHeight;
            return 'position:absolute;left:0;top:0;width:' + dw + 'px;height:' + dh + 'px;transform:scale(' + s + ');transform-origin:top left;';
        },
        get sortedLayoutElements() {
            return [...this.layoutElements].sort((a, b) => (Number(a.z_index) || 0) - (Number(b.z_index) || 0));
        },
        layoutElType(el) {
            if (!el) return '';
            const raw = el.type;
            if (raw != null && String(raw).trim() !== '') return String(raw).toLowerCase().trim();
            if (el.seat_id) return 'seat';
            return '';
        },
        layoutElementWrapperStyle(el) {
            const b = window.computeLayoutContentBoundsFromElements(this.layoutElements);
            const pad = window.LAYOUT_MAP_CONTENT_PAD;
            const x = Number(el.x) || 0;
            const y = Number(el.y) || 0;
            const w = Math.max(8, Number(el.w) || 48);
            const h = Math.max(8, Number(el.h) || 48);
            const ox = b ? (x - b.minX + pad) : x;
            const oy = b ? (y - b.minY + pad) : y;
            const rot = Number(el.rotation) || 0;
            const z = Number(el.z_index) || 0;
            return `left:${ox}px;top:${oy}px;width:${w}px;height:${h}px;transform:rotate(${rot}deg);z-index:${z};`;
        },
        isSelectedSeat(el) {
            return el && el.seat && selectedSet.has(parseInt(el.seat.id, 10));
        },
        layoutSeatFaceStyle(el) {
            if (!el || !el.seat) return 'background-color:#1e293b;border-color:#334155;color:#64748b;opacity:0.45;';
            if (this.isSelectedSeat(el)) {
                return 'background-color:#e50914;border-color:#fecaca;color:#fff;box-shadow:0 0 0 2px #fff, 0 0 0 5px rgba(229,9,20,0.95);opacity:1;';
            }
            const sec = el.seat.section_id != null ? parseInt(el.seat.section_id, 10) : 0;
            const pal = this.sectionPalettesById[sec];
            if (pal && pal.fill) {
                return `background-color:${pal.fill};border-color:${pal.stroke};color:${pal.text};opacity:0.35;`;
            }
            return 'background-color:#15803d;border-color:#14532d;color:#fff;opacity:0.35;';
        },
    };
}
</script>
@endpush
@endif
