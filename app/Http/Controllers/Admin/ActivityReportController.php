<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityReportService;
use App\Support\ActivityDuration;
use App\Support\ActivityRouteLabels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ActivityReportController extends Controller
{
    public function __construct(private readonly ActivityReportService $reports) {}

    public function index(Request $request): View
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:120'],
            'path' => ['nullable', 'string', 'max:180'],
            'kind' => ['nullable', 'in:all,pages,actions'],
        ]);

        $filters = $this->reports->filters($request);

        return view('admin.reports.activity', [
            'filters' => $filters,
            'summary' => $this->reports->summary($filters),
            'topPages' => $this->reports->topPages($filters),
            'actors' => $this->reports->actors($filters),
            'logs' => $this->reports->detail($filters),
        ]);
    }

    public function downloadPdf(Request $request): Response
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:120'],
            'path' => ['nullable', 'string', 'max:180'],
            'kind' => ['nullable', 'in:all,pages,actions'],
        ]);

        $filters = $this->reports->filters($request);
        $summary = $this->reports->summary($filters);
        $actors = $this->reports->actors($filters);
        $logs = $this->reports->detailQuery($filters)
            ->with(['user:id,name,email', 'trackedEvent:id,name'])
            ->limit(400)
            ->get();

        $pdf = Pdf::loadView('admin.reports.pdf.activity', [
            'filters' => $filters,
            'summary' => $summary,
            'actors' => $actors,
            'logs' => $logs,
            'duration' => ActivityDuration::class,
            'routes' => ActivityRouteLabels::class,
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('reporte-actividad-'.now()->format('Y-m-d').'.pdf');
    }
}
