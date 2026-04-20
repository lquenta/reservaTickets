<?php

namespace Tests\Unit;

use App\Support\SectionLayoutColors;
use PHPUnit\Framework\TestCase;

class SectionLayoutColorsTest extends TestCase
{
    public function test_rejects_black_and_near_black(): void
    {
        $this->assertFalse(SectionLayoutColors::isAllowed('#000000'));
        $this->assertFalse(SectionLayoutColors::isAllowed('#0a0a0a'));
        $this->assertTrue(SectionLayoutColors::isAllowed('#3d3d3d'));
    }

    public function test_rejects_red_family(): void
    {
        $this->assertFalse(SectionLayoutColors::isAllowed('#ff0000'));
        $this->assertFalse(SectionLayoutColors::isAllowed('#e50914'));
        $this->assertFalse(SectionLayoutColors::isAllowed('#dc2626'));
        $this->assertTrue(SectionLayoutColors::isAllowed('#f472b6'));
    }

    public function test_accepts_common_safe_colors(): void
    {
        $this->assertTrue(SectionLayoutColors::isAllowed('#2563eb'));
        $this->assertTrue(SectionLayoutColors::isAllowed('#059669'));
        $this->assertTrue(SectionLayoutColors::isAllowed('#FFFFFF'));
    }

    public function test_normalize(): void
    {
        $this->assertSame('#2563EB', SectionLayoutColors::normalize('#2563eb'));
        $this->assertNull(SectionLayoutColors::normalize(''));
        $this->assertNull(SectionLayoutColors::normalize('#12'));
    }
}
