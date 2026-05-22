<script>
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
</script>
