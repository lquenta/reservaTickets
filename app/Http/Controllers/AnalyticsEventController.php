<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsEventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_name' => ['required', 'string', 'max:80'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'path' => ['nullable', 'string', 'max:512'],
            'referrer' => ['nullable', 'string', 'max:512'],
        ]);

        $sessionId = (string) ($request->session()->getId() ?: '');
        if ($sessionId === '') {
            $sessionId = (string) $request->cookie('nova_session_id', '');
        }

        AnalyticsEvent::create([
            'event_name' => $data['event_name'],
            'session_id' => $sessionId !== '' ? $sessionId : null,
            'user_id' => auth()->id(),
            'event_id' => $data['event_id'] ?? null,
            'ip_address' => $request->ip(),
            'path' => $data['path'] ?? $request->path(),
            'referrer' => $data['referrer'] ?? $request->headers->get('referer'),
            'device_type' => $this->resolveDeviceType((string) $request->userAgent()),
            'occurred_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    private function resolveDeviceType(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
