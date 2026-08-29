<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiquetas de asientos — {{ $eventName }}</title>
    <style>
        @page { margin: 6mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; color: #1e1b4b; }
        .page { page-break-after: always; }
        .page-last { page-break-after: auto; }
        table.sheet { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td.cell { padding: 0; overflow: hidden; border: 0.35pt dashed #94a3b8; }
        table.inner { width: 100%; height: 100%; border-collapse: collapse; table-layout: fixed; }
        td.section {
            height: 14%;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #334155;
            padding: 1mm 1.5mm 0 1.5mm;
        }
        td.seat {
            height: 86%;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            color: #4B0082;
            line-height: 0.85;
            padding: 0 1mm 1.5mm 1mm;
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
                        <td class="cell" style="width: {{ $colWidthPct }}%; height: {{ $rowHeightMm }}mm;{{ !empty($label['overlay_tint']) ? ' background-color: '.$label['overlay_tint'].';' : '' }}">
                            <table class="inner">
                                <tr>
                                    <td class="section" style="font-size: {{ $fonts['section'] }}pt;">{{ $label['section_name'] ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="seat" style="font-size: {{ $label['seat_font'] ?? $fonts['seat'] }}pt;">{{ $label['label'] }}</td>
                                </tr>
                            </table>
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
