@if(!empty($sectionLegend))
    <div class="mb-4 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 text-xs text-white/80" aria-label="Sectores">
        @foreach($sectionLegend as $item)
            <span class="inline-flex max-w-full items-center gap-1.5">
                <span class="h-3 w-3 shrink-0 rounded-sm border" style="background-color:{{ $item['fill'] }};border-color:{{ $item['stroke'] }};"></span>
                <span class="truncate">{{ $item['name'] }}</span>
            </span>
        @endforeach
    </div>
@endif
