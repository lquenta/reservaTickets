<?php

namespace Tests\Unit;

use App\Models\Event;
use Carbon\Carbon;
use Tests\TestCase;

class EventPresaleTest extends TestCase
{
    private function makeEvent(array $attrs = []): Event
    {
        $event = new Event;
        foreach (array_merge([
            'presale_enabled' => true,
            'presale_discount_type' => Event::PRESALE_TYPE_PERCENT,
            'presale_discount_value' => 20,
            'presale_starts_at' => Carbon::parse('2026-07-01 00:00:00'),
            'presale_ends_at' => Carbon::parse('2026-07-31 23:59:59'),
        ], $attrs) as $key => $value) {
            $event->{$key} = $value;
        }

        return $event;
    }

    public function test_presale_inactive_when_disabled(): void
    {
        $event = $this->makeEvent(['presale_enabled' => false]);
        $at = Carbon::parse('2026-07-15 12:00:00');

        $this->assertFalse($event->isPresaleWindowActive($at));
        $this->assertSame(100.0, $event->applyPresaleDiscount(100, null, $at));
    }

    public function test_presale_inactive_outside_date_range(): void
    {
        $event = $this->makeEvent();
        $before = Carbon::parse('2026-06-30 23:59:59');
        $after = Carbon::parse('2026-08-01 00:00:00');

        $this->assertFalse($event->isPresaleWindowActive($before));
        $this->assertFalse($event->isPresaleWindowActive($after));
        $this->assertSame(100.0, $event->applyPresaleDiscount(100, null, $before));
    }

    public function test_percent_discount_applies_within_range(): void
    {
        $event = $this->makeEvent([
            'presale_discount_type' => Event::PRESALE_TYPE_PERCENT,
            'presale_discount_value' => 25,
        ]);
        $at = Carbon::parse('2026-07-15 12:00:00');

        $this->assertTrue($event->isPresaleWindowActive($at));
        $this->assertSame(75.0, $event->applyPresaleDiscount(100, null, $at));
    }

    public function test_fixed_discount_clamps_at_zero(): void
    {
        $event = $this->makeEvent([
            'presale_discount_type' => Event::PRESALE_TYPE_FIXED,
            'presale_discount_value' => 150,
        ]);
        $at = Carbon::parse('2026-07-15 12:00:00');

        $this->assertSame(0.0, $event->applyPresaleDiscount(100, null, $at));
        $this->assertSame(50.0, $event->applyPresaleDiscount(200, null, $at));
    }

    public function test_inclusive_range_boundaries(): void
    {
        $event = $this->makeEvent();
        $start = Carbon::parse('2026-07-01 00:00:00');
        $end = Carbon::parse('2026-07-31 23:59:59');

        $this->assertTrue($event->isPresaleWindowActive($start));
        $this->assertTrue($event->isPresaleWindowActive($end));
    }

    public function test_section_discount_overrides_event_level_value(): void
    {
        $event = $this->makeEvent([
            'presale_discount_type' => Event::PRESALE_TYPE_PERCENT,
            'presale_discount_value' => 10,
        ]);
        $at = Carbon::parse('2026-07-15 12:00:00');

        $section = new \App\Models\Section;
        $section->setRelation('pivot', new class
        {
            public $presale_discount_type = 'fixed';

            public $presale_discount_value = 40;
        });

        $this->assertSame(90.0, $event->applyPresaleDiscount(100, null, $at));
        $this->assertSame(60.0, $event->applyPresaleDiscount(100, $section, $at));
    }
}
