<?php

namespace Tests\Unit;

use App\Support\SeatLabelLayout;
use PHPUnit\Framework\TestCase;

class SeatLabelLayoutTest extends TestCase
{
    public function test_grid_prefers_portrait_a4_proportions(): void
    {
        $this->assertSame(['cols' => 1, 'rows' => 2], SeatLabelLayout::grid(2, 'portrait'));
        $this->assertSame(['cols' => 2, 'rows' => 2], SeatLabelLayout::grid(4, 'portrait'));
        $this->assertSame(['cols' => 2, 'rows' => 4], SeatLabelLayout::grid(8, 'portrait'));
        $this->assertSame(['cols' => 4, 'rows' => 4], SeatLabelLayout::grid(16, 'portrait'));
        $this->assertSame(['cols' => 4, 'rows' => 8], SeatLabelLayout::grid(32, 'portrait'));
    }

    public function test_grid_landscape_uses_more_columns(): void
    {
        $this->assertSame(['cols' => 2, 'rows' => 1], SeatLabelLayout::grid(2, 'landscape'));
        $this->assertSame(['cols' => 4, 'rows' => 2], SeatLabelLayout::grid(8, 'landscape'));
        $this->assertSame(['cols' => 8, 'rows' => 4], SeatLabelLayout::grid(32, 'landscape'));
    }

    public function test_page_count_rounds_up(): void
    {
        $this->assertSame(0, SeatLabelLayout::pageCount(0, 8));
        $this->assertSame(1, SeatLabelLayout::pageCount(8, 8));
        $this->assertSame(2, SeatLabelLayout::pageCount(9, 8));
        $this->assertSame(2, SeatLabelLayout::pageCount(5, 4));
        $this->assertSame(3, SeatLabelLayout::pageCount(17, 8));
    }

    public function test_rgba_uses_requested_alpha(): void
    {
        $this->assertSame('rgba(37, 99, 235, 0.2)', SeatLabelLayout::rgba('#2563EB', 20));
        $this->assertSame('rgba(37, 99, 235, 0)', SeatLabelLayout::rgba('#2563EB', 0));
        $this->assertNull(SeatLabelLayout::rgba(null, 20));
    }

    public function test_opacity_is_capped_so_it_cannot_cover_the_image(): void
    {
        $this->assertSame(80, SeatLabelLayout::normalizeOpacity(100));
        $this->assertSame('rgba(37, 99, 235, 0.8)', SeatLabelLayout::rgba('#2563EB', 200));
    }

    public function test_tinted_hex_mixes_with_white(): void
    {
        $this->assertSame('#FFFFFF', SeatLabelLayout::tintedHex('#2563EB', 0));
        $this->assertNotSame('#2563EB', SeatLabelLayout::tintedHex('#2563EB', 20));
    }
}
