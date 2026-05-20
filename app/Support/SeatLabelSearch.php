<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Interpreta búsquedas de butaca en formatos equivalentes: B2, b-2, B-2 → fila B + número 2.
 */
class SeatLabelSearch
{
    /**
     * @return array{row: int, number: int}|null
     */
    public static function parse(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', trim($raw));

        if (preg_match('/^([A-Za-z])-?(\d+)$/u', $normalized, $m)) {
            $row = ord(strtoupper($m[1])) - 64;
            if ($row >= 1 && $row <= 26) {
                return [
                    'row' => $row,
                    'number' => (int) $m[2],
                ];
            }
        }

        return null;
    }

    public static function applyToSeatQuery(Builder $query, string $raw): void
    {
        $parsed = self::parse($raw);
        if ($parsed !== null) {
            $query->where('row', $parsed['row'])
                ->where('number', $parsed['number']);

            return;
        }

        $term = '%'.trim($raw).'%';
        $query->where('number', 'like', $term);
    }
}
