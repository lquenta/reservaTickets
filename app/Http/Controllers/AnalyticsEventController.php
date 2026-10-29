<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsEventController extends Controller
{
    public function __construct(private readonly AnalyticsIngestService $ingest) {}

    public function store(Request $request): Response|JsonResponse
    {
        $events = $request->input('events');
        if (! is_array($events)) {
            if (! $request->filled('event_name')) {
                return response()->noContent();
            }

            $events = [[
                'event_name' => $request->input('event_name'),
                'event_id' => $request->input('event_id'),
                'path' => $request->input('path'),
                'referrer' => $request->input('referrer'),
                'route_name' => $request->input('route_name'),
                'label' => $request->input('label'),
                'view_token' => $request->input('view_token'),
                'duration_ms' => $request->input('duration_ms'),
            ]];
        }

        if (count($events) > AnalyticsIngestService::MAX_BATCH) {
            return response()->json(['message' => 'Demasiados eventos.'], 422);
        }

        $prepared = $this->ingest->prepare(
            $events,
            $request->hasSession() ? (string) $request->session()->getId() : null,
            $request->user()?->id,
            $request->ip(),
            (string) $request->userAgent(),
        );

        dispatch(function () use ($prepared) {
            app(AnalyticsIngestService::class)->write($prepared);
        })->afterResponse();

        return response()->noContent();
    }
}
