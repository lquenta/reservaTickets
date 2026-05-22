{{-- Marco del plano WYSIWYG (checkout): zoom + viewport. Requiere Alpine con layoutZoomPercent, layoutZoomOut, etc. --}}
<div class="pointer-events-auto mb-3 flex flex-wrap items-center justify-center gap-2 rounded-lg border border-red-900/50 bg-black/50 px-2 py-2 text-white shadow-inner">
    <span class="w-11 text-center font-mono text-xs tabular-nums text-white/90" x-text="layoutZoomPercent() + '%'"></span>
    <button type="button" class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-2 text-lg font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomOut()" title="Alejar plano" aria-label="Alejar plano">−</button>
    <button type="button" class="inline-flex h-9 items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-3 text-xs font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomResetFit()" title="Encajar al espacio" aria-label="Encajar plano">Encajar</button>
    <button type="button" class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-red-800/60 bg-black/60 px-2 text-lg font-semibold text-white hover:bg-red-950/50 active:scale-95" @click.prevent="layoutZoomIn()" title="Acercar plano" aria-label="Acercar plano">+</button>
    <span class="hidden text-[10px] text-white/45 sm:inline">Ctrl + rueda</span>
</div>
<div x-ref="layoutViewport"
     @resize.window="recalcLayoutViewportScale()"
     class="pointer-events-auto relative w-full min-h-[200px] max-h-[min(78dvh,900px)] touch-manipulation overflow-auto overscroll-contain rounded-xl border border-red-900/40 bg-[radial-gradient(circle,_rgba(255,255,255,0.12)_1px,_transparent_1px)] bg-[size:16px_16px] p-2 sm:max-h-[min(86vh,90dvh)]">
    <div class="relative mx-auto shrink-0" :style="layoutScaledHostStyle">
        <div class="relative" :style="layoutScaledStageStyle">
            {{ $slot }}
        </div>
    </div>
</div>
