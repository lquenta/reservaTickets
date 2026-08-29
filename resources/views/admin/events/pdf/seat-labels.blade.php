<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiquetas de asientos — {{ $eventName }}</title>
    <style>
        @page { margin: 6mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e1b4b; }
        .page { page-break-after: always; }
        .page-last { page-break-after: auto; }
        table.sheet {
            width: {{ $inner['width'] }}mm;
            height: {{ $inner['height'] }}mm;
            border-collapse: collapse;
            table-layout: fixed;
        }
        td.cell {
            width: {{ $cellWidthMm }}mm;
            height: {{ $rowHeightMm }}mm;
            padding: 0;
            overflow: hidden;
            border: 0.35pt dashed #94a3b8;
        }
        table.inner {
            width: {{ $cellWidthMm }}mm;
            height: {{ $rowHeightMm }}mm;
            border-collapse: collapse;
            table-layout: fixed;
        }
        td.section {
            width: {{ $cellWidthMm }}mm;
            height: {{ round($rowHeightMm * 0.16, 2) }}mm;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #334155;
            padding: 0.6mm 1.2mm 0 1.2mm;
            overflow: hidden;
        }
        td.seat {
            width: {{ $cellWidthMm }}mm;
            height: {{ round($rowHeightMm * 0.84, 2) }}mm;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            color: #4B0082;
            line-height: 1;
            padding: 0 1.6mm 1.2mm 1.6mm;
            overflow: hidden;
            white-space: nowrap;
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
                        <td class="cell" style="{{ !empty($label['overlay_tint']) ? 'background-color: '.$label['overlay_tint'].';' : '' }}">
                            <table class="inner">
                                <tr>
                                    <td class="section" style="font-size: {{ $fonts['section'] }}pt;">{{ $label['section_name'] ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="seat" style="font-size: {{ $fonts['seat'] }}pt;">{{ $label['label'] }}</td>
                                </tr>
                            </table>
                        </td>
                    @endforeach
                    @for($pad = $rowCells->count(); $pad < $cols; $pad++)
                        <td class="cell empty"></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
</body>
</html>
