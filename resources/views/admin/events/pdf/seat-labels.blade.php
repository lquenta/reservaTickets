<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiquetas de asientos — {{ $eventName }}</title>
    <style>
        @page { margin: 6mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; color: #0f172a; }
        .page { page-break-after: always; }
        .page-last { page-break-after: auto; }
        table.sheet { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td.cell { padding: 0; vertical-align: top; overflow: hidden; border: 0.35pt dashed #94a3b8; }
        .label-box { position: relative; overflow: hidden; }
        .label-cover { position: absolute; left: 0; top: 0; width: 100%; height: 100%; }
        .label-overlay { position: absolute; left: 0; top: 0; width: 100%; height: 100%; }
        .label-body {
            position: relative;
            z-index: 2;
            height: 100%;
            padding: 2.4mm 2.8mm;
            text-align: center;
        }
        .label-event { font-weight: bold; color: #1e293b; line-height: 1.2; margin: 0 0 1mm 0; }
        .label-meta { color: #334155; line-height: 1.25; margin: 0 0 1.5mm 0; }
        .label-section { font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; color: #0f172a; margin: 0 0 1.5mm 0; }
        .seat-pill {
            display: inline-block;
            background: rgba(255,255,255,0.88);
            border: 0.6pt solid #cbd5e1;
            border-radius: 3pt;
            padding: 1.2mm 2.4mm;
            font-weight: bold;
            font-family: DejaVu Sans, sans-serif;
            color: #4B0082;
            line-height: 1.1;
        }
        .empty { border: none; }
    </style>
</head>
<body>
@foreach($pages as $pageIndex => $pageLabels)
    @php
        $isLast = $pageIndex === $pages->count() - 1;
        $rowsOfCells = $pageLabels->values()->chunk($cols);
    @endphp
    <div class="{{ $isLast ? 'page-last' : 'page' }}">
        <table class="sheet">
            @foreach($rowsOfCells as $rowCells)
                <tr>
                    @foreach($rowCells as $label)
                        <td class="cell" style="width: {{ $colWidthPct }}%; height: {{ $rowHeightMm }}mm;">
                            <div class="label-box" style="height: {{ $rowHeightMm }}mm;{{ empty($coverPath) && !empty($label['overlay_tint']) ? ' background-color: '.$label['overlay_tint'].';' : '' }}">
                                @if($coverPath)
                                    <img class="label-cover" src="{{ $coverPath }}" alt="" width="400" height="280" />
                                @endif
                                @if(!empty($label['overlay_rgba']))
                                    <div class="label-overlay" style="background-color: {{ $label['overlay_rgba'] }};"></div>
                                @endif
                                <div class="label-body">
                                    <div class="label-event" style="font-size: {{ $fonts['section'] }}pt;">{{ $eventName }}</div>
                                    <div class="label-meta" style="font-size: {{ $fonts['meta'] }}pt;">{{ $eventDate }} · {{ $venueName }}</div>
                                    @if(!empty($label['section_name']))
                                        <div class="label-section" style="font-size: {{ $fonts['section'] }}pt;">{{ $label['section_name'] }}</div>
                                    @endif
                                    <div>
                                        <span class="seat-pill" style="font-size: {{ $fonts['seat'] }}pt;">{{ $label['label'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    @endforeach
                    @for($pad = $rowCells->count(); $pad < $cols; $pad++)
                        <td class="cell empty" style="width: {{ $colWidthPct }}%; height: {{ $rowHeightMm }}mm;"></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
</body>
</html>
