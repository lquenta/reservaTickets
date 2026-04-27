<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardMetricsService $metricsService) {}

    public function index(Request $request): View
    {
        $filters = $this->metricsService->normalizeFilters($request->only([
            'date_from',
            'date_to',
            'event_scope',
            'event_id',
        ]));

        $events = $this->metricsService->eventsForFilter($filters['event_scope']);
        $metrics = $this->metricsService->build($filters);

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'filters' => $filters,
            'eventsForFilter' => $events,
        ]);
    }
}
