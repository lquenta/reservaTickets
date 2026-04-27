<?php

namespace Tests\Unit;

use App\Services\AdminDashboardMetricsService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AdminDashboardMetricsServiceTest extends TestCase
{
    public function test_normalize_filters_sets_defaults_and_scope(): void
    {
        $service = new AdminDashboardMetricsService();
        $filters = $service->normalizeFilters([
            'event_scope' => 'all',
            'event_id' => '12',
        ]);

        $this->assertSame('all', $filters['event_scope']);
        $this->assertSame(12, $filters['event_id']);
        $this->assertInstanceOf(Carbon::class, $filters['date_from']);
        $this->assertInstanceOf(Carbon::class, $filters['date_to']);
        $this->assertTrue($filters['date_from']->lte($filters['date_to']));
    }
}
