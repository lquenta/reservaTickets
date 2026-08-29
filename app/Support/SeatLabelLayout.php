<?php

namespace App\Support;

final class SeatLabelLayout
{
    public const DEFAULT_PER_PAGE = 8;

    public const DEFAULT_OPACITY = 20;

    public const MIN_PER_PAGE = 1;

    public const MAX_PER_PAGE = 64;

    public const MIN_OPACITY = 0;

    public const MAX_OPACITY = 80;

    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [1, 2, 4, 8, 16, 32, 64];

    /**
     * @return array{cols: int, rows: int}
     */
    public static function grid(int $perPage, string $orientation = 'portrait'): array
    {
        $perPage = self::normalizePerPage($perPage);
        $targetAspect = $orientation === 'landscape' ? (297 / 210) : (210 / 297);

        $bestCols = 1;
        $bestRows = $perPage;
        $bestScore = PHP_FLOAT_MAX;

        for ($cols = 1; $cols <= $perPage; $cols++) {
            if ($perPage % $cols !== 0) {
                continue;
            }
            $rows = intdiv($perPage, $cols);
            $score = abs(($cols / $rows) - $targetAspect);
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestCols = $cols;
                $bestRows = $rows;
            }
        }

        return ['cols' => $bestCols, 'rows' => $bestRows];
    }

    public static function pageCount(int $totalLabels, int $perPage): int
    {
        $perPage = self::normalizePerPage($perPage);
        if ($totalLabels <= 0) {
            return 0;
        }

        return (int) ceil($totalLabels / $perPage);
    }

    public static function normalizePerPage(int $perPage): int
    {
        return max(self::MIN_PER_PAGE, min(self::MAX_PER_PAGE, $perPage));
    }

    public static function normalizeOpacity(int $percent): int
    {
        return max(self::MIN_OPACITY, min(self::MAX_OPACITY, $percent));
    }

    /**
     * Ancho aproximado de un glifo bold DejaVu (em). Conservador para que no desborde en DomPDF.
     */
    public const GLYPH_WIDTH_EM = 0.85;

    /**
     * Tamaños de fuente para una celda: el código de butaca llena el espacio sin salirse.
     *
     * @return array{seat: int, section: int, meta: int}
     */
    public static function fontSizesForCell(float $cellWidthMm, float $cellHeightMm, int $seatChars = 3): array
    {
        $mmToPt = 72 / 25.4;
        $seatChars = max(1, $seatChars);
        $section = max(6, min(14, (int) round($cellHeightMm * $mmToPt * 0.08)));
        $usableW = max(6.0, $cellWidthMm - 6.0);
        $usableH = max(8.0, $cellHeightMm * 0.72);
        $fromHeight = $usableH * $mmToPt * 0.82;
        $fromWidth = ($usableW * $mmToPt) / ($seatChars * self::GLYPH_WIDTH_EM);
        $seat = (int) round(max(12, min($fromHeight, $fromWidth)));

        return [
            'seat' => $seat,
            'section' => $section,
            'meta' => max(6, (int) round($section * 0.75)),
        ];
    }

    /**
     * @return array{seat: int, section: int, meta: int}
     */
    public static function fontSizes(int $perPage, string $orientation = 'portrait'): array
    {
        $perPage = self::normalizePerPage($perPage);
        $grid = self::grid($perPage, $orientation);
        $inner = self::pageInnerMm($orientation);
        $cellW = $grid['cols'] > 0 ? $inner['width'] / $grid['cols'] : $inner['width'];
        $cellH = $grid['rows'] > 0 ? $inner['height'] / $grid['rows'] : $inner['height'];

        return self::fontSizesForCell($cellW, $cellH, 3);
    }

    /**
     * Área útil A4 en mm (márgenes 6 mm).
     *
     * @return array{width: float, height: float}
     */
    public static function pageInnerMm(string $orientation = 'portrait'): array
    {
        $margin = 6.0;
        if ($orientation === 'landscape') {
            return [
                'width' => 297.0 - (2 * $margin),
                'height' => 210.0 - (2 * $margin),
            ];
        }

        return [
            'width' => 210.0 - (2 * $margin),
            'height' => 297.0 - (2 * $margin),
        ];
    }

    public static function rgba(?string $hex, int $opacityPercent): ?string
    {
        $rgb = self::rgb($hex);
        if ($rgb === null) {
            return null;
        }
        $a = round(self::normalizeOpacity($opacityPercent) / 100, 3);

        return sprintf('rgba(%d, %d, %d, %s)', $rgb[0], $rgb[1], $rgb[2], $a);
    }

    /**
     * Color sólido equivalente a superponer $hex al blanco con la opacidad dada.
     */
    public static function tintedHex(?string $hex, int $opacityPercent): ?string
    {
        $rgb = self::rgb($hex);
        if ($rgb === null) {
            return null;
        }
        $a = self::normalizeOpacity($opacityPercent) / 100;
        $r = (int) round(255 * (1 - $a) + $rgb[0] * $a);
        $g = (int) round(255 * (1 - $a) + $rgb[1] * $a);
        $b = (int) round(255 * (1 - $a) + $rgb[2] * $a);

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    public static function rgb(?string $hex): ?array
    {
        $normalized = SectionLayoutColors::normalize($hex);
        if ($normalized === null) {
            return null;
        }
        $h = ltrim($normalized, '#');

        return [
            hexdec(substr($h, 0, 2)),
            hexdec(substr($h, 2, 2)),
            hexdec(substr($h, 4, 2)),
        ];
    }
}
