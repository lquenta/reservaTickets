<script>
    function adminEventSeatsMap(config) {
        const layoutElements = Array.isArray(config.layoutElements) ? config.layoutElements : [];
        const sectionPalettesById = config.sectionPalettesById || {};
        const readonly = !!config.readonly;
        const lc = config.layoutCanvas || {};
        const layoutCanvasW = lc.width != null && Number(lc.width) > 0 ? Number(lc.width) : null;
        const layoutCanvasH = lc.height != null && Number(lc.height) > 0 ? Number(lc.height) : null;

        return {
            readonly,
            layoutElements,
            layoutCanvas: lc,
            sectionPalettesById,
            _layoutViewportScale: 1,
            _layoutViewportUserScale: null,
            _layoutViewportRo: null,
            _layoutCheckoutWheelBound: false,
            _layoutOrientationHandler: null,
            hasCustomLayout() {
                return layoutElements.length > 0;
            },
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
                    el.addEventListener('wheel', function (e) {
                        if (!(e.ctrlKey || e.metaKey)) return;
                        e.preventDefault();
                        if (e.deltaY > 0) self.layoutZoomOut();
                        else self.layoutZoomIn();
                    }, { passive: false });
                    this._layoutOrientationHandler = function () {
                        setTimeout(function () { self.recalcLayoutViewportScale(); }, 200);
                    };
                    window.addEventListener('orientationchange', this._layoutOrientationHandler);
                }
            },
            recalcLayoutViewportScale() {
                const el = this.$refs.layoutViewport;
                if (!el) return;
                const pad = window.LAYOUT_CHECKOUT_ZOOM_PAD;
                const dw = this.layoutDesignWidth;
                const dh = this.layoutDesignHeight;
                const fit = window.layoutCheckoutFitScale(el, dw, dh, pad);
                const u = this._layoutViewportUserScale;
                if (u == null || !Number.isFinite(Number(u))) {
                    this._layoutViewportScale = fit;
                } else {
                    this._layoutViewportScale = Math.max(window.LAYOUT_CHECKOUT_MIN_SCALE, Math.min(Number(u), 1));
                }
            },
            layoutZoomPercent() {
                return Math.round((Number(this._layoutViewportScale) || 1) * 100);
            },
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
                if (vp) {
                    vp.scrollLeft = 0;
                    vp.scrollTop = 0;
                }
                this.recalcLayoutViewportScale();
            },
            inferLayoutCanvasWidth() {
                if (!layoutElements.length) return 960;
                let m = 320;
                for (let i = 0; i < layoutElements.length; i++) {
                    const el = layoutElements[i];
                    const r = (Number(el.x) || 0) + Math.max(8, Number(el.w) || 48) + 48;
                    if (r > m) m = r;
                }
                return Math.ceil(m);
            },
            inferLayoutCanvasHeight() {
                if (!layoutElements.length) return 640;
                let m = 420;
                for (let i = 0; i < layoutElements.length; i++) {
                    const el = layoutElements[i];
                    const r = (Number(el.y) || 0) + Math.max(8, Number(el.h) || 48) + 48;
                    if (r > m) m = r;
                }
                return Math.ceil(m);
            },
            get layoutDesignWidth() {
                const b = window.computeLayoutContentBoundsFromElements(this.layoutElements);
                if (b) {
                    return Math.max(200, Math.ceil(b.maxX - b.minX + 2 * window.LAYOUT_MAP_CONTENT_PAD));
                }
                if (layoutCanvasW != null) return layoutCanvasW;
                return this.inferLayoutCanvasWidth();
            },
            get layoutDesignHeight() {
                const b = window.computeLayoutContentBoundsFromElements(this.layoutElements);
                if (b) {
                    return Math.max(200, Math.ceil(b.maxY - b.minY + 2 * window.LAYOUT_MAP_CONTENT_PAD));
                }
                if (layoutCanvasH != null) return layoutCanvasH;
                return this.inferLayoutCanvasHeight();
            },
            get layoutScaledHostStyle() {
                const s = this._layoutViewportScale;
                const dw = this.layoutDesignWidth;
                const dh = this.layoutDesignHeight;
                return 'width:' + (dw * s) + 'px;height:' + (dh * s) + 'px;';
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
                if (!el) return '';
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
            sectionPaletteEntry(sectionId) {
                const sid = parseInt(sectionId, 10) || 0;
                const pal = this.sectionPalettesById && this.sectionPalettesById[sid];
                if (pal && pal.fill) {
                    return { bg: pal.fill, border: pal.stroke, text: pal.text };
                }
                return { bg: '#2563eb', border: '#1e40af', text: '#ffffff' };
            },
            seatSectionIdFromLayout(el) {
                if (!el || !el.seat) return 0;
                const sid = el.seat.section_id;
                return sid != null ? parseInt(sid, 10) || 0 : 0;
            },
            seatUnavailable(el) {
                if (!el || !el.seat) return true;
                const s = el.seat;
                return !!(s.occupied || s.blocked_globally);
            },
            seatBlockedForEvent(el) {
                return !!(el && el.seat && el.seat.blocked_for_event);
            },
            seatPublicAvailable(el) {
                if (!el || !el.seat) return false;
                return !this.seatUnavailable(el) && !this.seatBlockedForEvent(el);
            },
            layoutSeatFaceStyle(el) {
                if (!el || this.layoutElType(el) !== 'seat') return '';
                if (!el.seat) {
                    return 'background-color:#15803d;border-color:#14532d;color:#fff;box-shadow:0 1px 2px rgba(0,0,0,0.35);';
                }
                const shadow = '0 1px 2px rgba(0,0,0,0.35)';
                if (this.seatUnavailable(el)) {
                    return 'background-color:#1e293b;border-color:#334155;color:#64748b;box-shadow:none;';
                }
                if (this.seatBlockedForEvent(el)) {
                    return 'background-color:#1e293b;border-color:#475569;color:#94a3b8;box-shadow:0 0 0 2px rgba(251,191,36,0.85);';
                }
                const sec = this.seatSectionIdFromLayout(el);
                if (sec) {
                    const pal = this.sectionPaletteEntry(sec);
                    return `background-color:${pal.bg};border-color:${pal.border};color:${pal.text};box-shadow:${shadow};`;
                }
                return `background-color:#15803d;border-color:#14532d;color:#fff;box-shadow:${shadow};`;
            },
            layoutSeatClass(el) {
                if (readonly) return 'cursor-default';
                if (!el || !el.seat) return 'cursor-not-allowed';
                if (this.seatUnavailable(el)) return 'cursor-default';
                if (this.seatPublicAvailable(el) || this.seatBlockedForEvent(el)) {
                    return 'cursor-pointer hover:brightness-110';
                }
                return 'cursor-default';
            },
            layoutStageSpeakerFaceStyle() {
                return 'opacity:1;';
            },
            gridSeatFaceStyle(sectionId, unavailable) {
                if (unavailable) {
                    return 'background-color:#1e293b;border-color:#334155;color:#64748b;';
                }
                const pal = this.sectionPaletteEntry(sectionId);
                return `background-color:${pal.bg};border-color:${pal.border};color:${pal.text};`;
            },
        };
    }
</script>
