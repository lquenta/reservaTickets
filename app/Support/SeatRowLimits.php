<?php

namespace App\Support;

/**
 * Máximo de número de butaca por fila. Una fila sin entrada usa todas las columnas del venue.
 *
 * @phpstan-type Limits array<string, int>
 */
class SeatRowLimits
{
    /**
     * @param  array<mixed>|null  $limits
     * @return array<string, int>
     */
    public static function normalize(?array $limits, int $maxRow, int $maxCol): array
    {
        if ($limits === null || $limits === []) {
            return [];
        }

        $maxRow = max(1, $maxRow);
        $maxCol = max(1, $maxCol);
        $out = [];

        foreach ($limits as $row => $max) {
            if (! is_numeric((string) $row) || ! is_numeric((string) $max)) {
                continue;
            }
            $rowNumber = (int) $row;
            $cap = (int) $max;
            if ($rowNumber < 1 || $rowNumber > $maxRow) {
                continue;
            }
            $out[(string) $rowNumber] = max(1, min($maxCol, $cap));
        }

        ksort($out, SORT_NATURAL);

        return $out;
    }

    /**
     * @param  array<string, int>|null  $limits
     */
    public static function maxForRow(?array $limits, int $row, int $columns): int
    {
        $columns = max(0, $columns);
        if ($limits === null || ! array_key_exists((string) $row, $limits)) {
            return $columns;
        }

        return max(0, min($columns, (int) $limits[(string) $row]));
    }
}
