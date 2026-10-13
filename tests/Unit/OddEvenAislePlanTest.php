<?php

namespace Tests\Unit;

use App\Support\OddEvenAislePlan;
use App\Support\SeatRowLimits;
use PHPUnit\Framework\TestCase;

class OddEvenAislePlanTest extends TestCase
{
    public function test_short_row_puts_odds_left_evens_right_and_fillers_outside(): void
    {
        $plan = OddEvenAislePlan::cellsForRow(18, 24);

        $this->assertSame(12, $plan['left_count']);
        $this->assertCount(24, $plan['cells']);

        $numbers = array_map(fn (array $cell) => $cell['kind'] === 'seat' ? $cell['number'] : null, $plan['cells']);
        $this->assertSame(
            [null, null, null, 17, 15, 13, 11, 9, 7, 5, 3, 1, 2, 4, 6, 8, 10, 12, 14, 16, 18, null, null, null],
            $numbers
        );
        $this->assertSame('unavailable', $plan['cells'][0]['kind']);
        $this->assertSame('seat', $plan['cells'][11]['kind']);
        $this->assertSame(1, $plan['cells'][11]['number']);
        $this->assertSame(2, $plan['cells'][12]['number']);
    }

    public function test_full_row_has_no_fillers(): void
    {
        $plan = OddEvenAislePlan::cellsForRow(24, 24);
        $kinds = array_column($plan['cells'], 'kind');

        $this->assertNotContains('unavailable', $kinds);
        $this->assertSame(23, $plan['cells'][0]['number']);
        $this->assertSame(1, $plan['cells'][11]['number']);
        $this->assertSame(2, $plan['cells'][12]['number']);
        $this->assertSame(24, $plan['cells'][23]['number']);
    }

    public function test_domingo_profile_matches_the_house_taper(): void
    {
        $limits = OddEvenAislePlan::domingo31RowLimits();

        $this->assertSame(18, $limits['1']);
        $this->assertSame(18, $limits['2']);
        $this->assertSame(20, $limits['3']);
        $this->assertSame(22, $limits['6']);
        $this->assertSame(24, $limits['7']);
        $this->assertSame(24, $limits['12']);
        $this->assertSame(22, $limits['14']);
        $this->assertSame(20, $limits['16']);
        $this->assertSame(350, array_sum($limits));
    }

    public function test_row_without_a_limit_uses_every_column(): void
    {
        $limits = SeatRowLimits::normalize(['1' => 18, '99' => 10, '2' => 30], 16, 24);

        $this->assertSame(['1' => 18, '2' => 24], $limits);
        $this->assertSame(18, SeatRowLimits::maxForRow($limits, 1, 24));
        $this->assertSame(24, SeatRowLimits::maxForRow($limits, 3, 24));
    }
}
