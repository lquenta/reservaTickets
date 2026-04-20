<?php

namespace App\Support;

use App\Models\Section;

final class SectionLayoutColors
{
    /**
     * Colores por defecto cuando no hay layout_color (sin rojos ni negros).
     *
     * @var list<array{fill: string, stroke: string, text: string}>
     */
    private const FALLBACK_TRIPLETS = [
        ['fill' => '#2563eb', 'stroke' => '#1e40af', 'text' => '#ffffff'],
        ['fill' => '#9333ea', 'stroke' => '#6b21a8', 'text' => '#ffffff'],
        ['fill' => '#d97706', 'stroke' => '#b45309', 'text' => '#fffbeb'],
        ['fill' => '#0891b2', 'stroke' => '#0e7490', 'text' => '#ffffff'],
        ['fill' => '#059669', 'stroke' => '#047857', 'text' => '#ffffff'],
        ['fill' => '#65a30d', 'stroke' => '#4d7c0f', 'text' => '#fffbeb'],
        ['fill' => '#db2777', 'stroke' => '#9d174d', 'text' => '#ffffff'],
    ];

    public static function normalize(?string $hex): ?string
    {
        if ($hex === null) {
            return null;
        }
        $hex = strtoupper(ltrim(trim($hex), '#'));
        if ($hex === '') {
            return null;
        }
        if (! preg_match('/^[0-9A-F]{6}$/', $hex)) {
            return null;
        }

        return '#'.$hex;
    }

    /**
     * Colores permitidos para sectores: excluye negro/casi negro y rojos (reservados visualmente para no disponible).
     */
    public static function isAllowed(string $normalizedHex): bool
    {
        $hex = ltrim(strtoupper(trim($normalizedHex)), '#');
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return false;
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        if (max($r, $g, $b) < 52 && ($r + $g + $b) < 165) {
            return false;
        }

        if ($r >= 130 && $g <= 100 && $b <= 100) {
            return false;
        }

        return true;
    }

    /**
     * @return array{fill: string, stroke: string, text: string}
     */
    public static function tripletFromFill(string $normalizedHex): array
    {
        $hex = ltrim($normalizedHex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $stroke = self::darkenHex($r, $g, $b, 0.72);
        $l = self::relativeLuminance($r, $g, $b);
        $text = $l > 0.52 ? '#111827' : '#ffffff';

        return [
            'fill' => '#'.$hex,
            'stroke' => $stroke,
            'text' => $text,
        ];
    }

    /**
     * @return array{fill: string, stroke: string, text: string}
     */
    public static function fallbackTripletForSectionId(int $sectionId): array
    {
        $idx = abs($sectionId) % count(self::FALLBACK_TRIPLETS);

        return self::FALLBACK_TRIPLETS[$idx];
    }

    /**
     * @return array{fill: string, stroke: string, text: string}
     */
    public static function tripletForSection(Section $section): array
    {
        $n = self::normalize($section->layout_color ?? null);
        if ($n !== null && self::isAllowed($n)) {
            return self::tripletFromFill($n);
        }

        return self::fallbackTripletForSectionId((int) $section->id);
    }

    private static function darkenHex(int $r, int $g, int $b, float $factor): string
    {
        $r2 = (int) max(0, min(255, round($r * $factor)));
        $g2 = (int) max(0, min(255, round($g * $factor)));
        $b2 = (int) max(0, min(255, round($b * $factor)));

        return sprintf('#%02X%02X%02X', $r2, $g2, $b2);
    }

    private static function relativeLuminance(int $r, int $g, int $b): float
    {
        $srgb = function (int $c): float {
            $x = $c / 255;

            return $x <= 0.03928 ? $x / 12.92 : (($x + 0.055) / 1.055) ** 2.4;
        };
        $R = $srgb($r);
        $G = $srgb($g);
        $B = $srgb($b);

        return 0.2126 * $R + 0.7152 * $G + 0.0722 * $B;
    }
}
