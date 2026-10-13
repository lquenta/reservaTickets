<?php

namespace App\Support;

/**
 * Orden visual de una fila con pasillo central: impares descendentes a la izquierda, pares ascendentes a la derecha.
 * El número de butaca no cambia; solo el puesto en el plano.
 */
class OddEvenAislePlan
{
    public const SOURCE = 'odd-even-aisle';

    /**
     * Largos del plano ESCENARIO DOMINGO 31 (filas A–P).
     *
     * @return array<string, int>
     */
    public static function domingo31RowLimits(): array
    {
        $byRow = [
            1 => 18,
            2 => 18,
            3 => 20,
            4 => 22,
            5 => 22,
            6 => 22,
            7 => 24,
            8 => 24,
            9 => 24,
            10 => 24,
            11 => 24,
            12 => 24,
            13 => 22,
            14 => 22,
            15 => 20,
            16 => 20,
        ];

        $limits = [];
        foreach ($byRow as $row => $max) {
            $limits[(string) $row] = $max;
        }

        return $limits;
    }

    /**
     * Celdas de izquierda a derecha. El pasillo no es una celda: va entre el último impar y el primer par.
     *
     * @return array{cells: list<array{kind: string, number: int|null}>, left_count: int}
     */
    public static function cellsForRow(int $rowMax, int $houseMax): array
    {
        $houseMax = max(1, $houseMax);
        $rowMax = max(0, min($rowMax, $houseMax));

        $cells = [];
        $highestOdd = $houseMax % 2 === 1 ? $houseMax : $houseMax - 1;
        for ($n = $highestOdd; $n >= 1; $n -= 2) {
            $cells[] = $n <= $rowMax
                ? ['kind' => 'seat', 'number' => $n]
                : ['kind' => 'unavailable', 'number' => null];
        }

        $leftCount = count($cells);
        $highestEven = $houseMax % 2 === 0 ? $houseMax : $houseMax - 1;
        for ($n = 2; $n <= $highestEven; $n += 2) {
            $cells[] = $n <= $rowMax
                ? ['kind' => 'seat', 'number' => $n]
                : ['kind' => 'unavailable', 'number' => null];
        }

        return ['cells' => $cells, 'left_count' => $leftCount];
    }
}
