<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Event;
use Illuminate\Support\Carbon;

class AnalyticsIngestService
{
    public const MAX_BATCH = 25;

    public const MAX_DURATION_MS = 1_800_000;

    /** @var list<string> */
    private const FUNNEL_WITH_IP = [
        AnalyticsEvent::EVENT_VIEW_EVENT,
        AnalyticsEvent::EVENT_BEGIN_RESERVATION,
        AnalyticsEvent::EVENT_BEGIN_CHECKOUT,
    ];

    /**
     * @param  list<mixed>  $events
     * @return array{session_id: ?string, user_id: ?int, inserts: list<array<string, mixed>>, durations: list<array{view_token: string, duration_ms: int}>}
     */
    public function prepare(array $events, ?string $sessionId, ?int $userId, ?string $ip, string $userAgent): array
    {
        $sessionId = $sessionId !== null && $sessionId !== '' ? mb_substr($sessionId, 0, 120) : null;
        $device = $this->deviceType($userAgent);
        $now = now();
        $inserts = [];
        /** @var list<array{view_token: string, duration_ms: int}> $durations */
        $durations = [];

        foreach (array_slice($events, 0, self::MAX_BATCH) as $event) {
            if (! is_array($event)) {
                continue;
            }

            $name = isset($event['event_name']) ? mb_substr((string) $event['event_name'], 0, 80) : '';
            if (! in_array($name, AnalyticsEvent::CLIENT_NAMES, true)) {
                continue;
            }

            $token = $this->viewToken($event['view_token'] ?? null);
            $duration = $this->duration($event['duration_ms'] ?? null);

            if ($name === AnalyticsEvent::EVENT_PAGE_DURATION) {
                if ($token !== null && $duration > 0) {
                    $this->stashDuration($durations, $token, $duration);
                }

                continue;
            }

            $rowDuration = $name === AnalyticsEvent::EVENT_PAGE_VIEW ? $duration : null;
            if ($name === AnalyticsEvent::EVENT_PAGE_VIEW && $token !== null) {
                $stashed = $this->pullDuration($durations, $token);
                if ($stashed !== null) {
                    $rowDuration = max($rowDuration ?? 0, $stashed);
                }
            }

            $inserts[] = [
                'event_name' => $name,
                'session_id' => $sessionId,
                'user_id' => $userId,
                'event_id' => $this->eventId($event['event_id'] ?? null),
                'ip_address' => in_array($name, self::FUNNEL_WITH_IP, true) ? $this->ip($ip) : null,
                'path' => $this->sanitizePath(isset($event['path']) ? (string) $event['path'] : null),
                'referrer' => $this->sanitizeReferrer(isset($event['referrer']) ? (string) $event['referrer'] : null),
                'label' => $this->sanitizeLabel(isset($event['label']) ? (string) $event['label'] : null),
                'route_name' => $this->routeName($event['route_name'] ?? null),
                'duration_ms' => $rowDuration,
                'view_token' => $name === AnalyticsEvent::EVENT_PAGE_VIEW ? $token : null,
                'device_type' => $device,
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'inserts' => $inserts,
            'durations' => $durations,
        ];
    }

    /**
     * @param  list<array{view_token: string, duration_ms: int}>  $durations
     */
    private function stashDuration(array &$durations, string $token, int $duration): void
    {
        foreach ($durations as $index => $item) {
            if ($item['view_token'] === $token) {
                $durations[$index]['duration_ms'] = max($item['duration_ms'], $duration);

                return;
            }
        }

        $durations[] = ['view_token' => $token, 'duration_ms' => $duration];
    }

    /**
     * @param  list<array{view_token: string, duration_ms: int}>  $durations
     */
    private function pullDuration(array &$durations, string $token): ?int
    {
        foreach ($durations as $index => $item) {
            if ($item['view_token'] !== $token) {
                continue;
            }

            unset($durations[$index]);
            $durations = array_values($durations);

            return $item['duration_ms'];
        }

        return null;
    }

    /**
     * @param  array{session_id: ?string, user_id: ?int, inserts: list<array<string, mixed>>, durations: list<array{view_token: string, duration_ms: int}>}  $prepared
     */
    public function write(array $prepared): void
    {
        $inserts = $this->withoutDuplicatePageViews($prepared['inserts']);
        $inserts = $this->nullUnknownEvents($inserts);

        if ($inserts !== []) {
            AnalyticsEvent::query()->insert($inserts);
        }

        foreach ($prepared['durations'] as $duration) {
            $this->raiseDuration(
                $duration['view_token'],
                $duration['duration_ms']
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $inserts
     * @return list<array<string, mixed>>
     */
    private function withoutDuplicatePageViews(array $inserts): array
    {
        $tokens = collect($inserts)
            ->where('event_name', AnalyticsEvent::EVENT_PAGE_VIEW)
            ->pluck('view_token')
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return $inserts;
        }

        $existing = AnalyticsEvent::query()
            ->where('event_name', AnalyticsEvent::EVENT_PAGE_VIEW)
            ->whereIn('view_token', $tokens->all())
            ->pluck('duration_ms', 'view_token');

        $kept = [];
        foreach ($inserts as $row) {
            if ($row['event_name'] === AnalyticsEvent::EVENT_PAGE_VIEW && $row['view_token'] && $existing->has($row['view_token'])) {
                $current = (int) ($existing[$row['view_token']] ?? 0);
                $incoming = (int) ($row['duration_ms'] ?? 0);
                if ($incoming > $current) {
                    $this->raiseDuration((string) $row['view_token'], $incoming);
                }

                continue;
            }

            $kept[] = $row;
        }

        return $kept;
    }

    /**
     * @param  list<array<string, mixed>>  $inserts
     * @return list<array<string, mixed>>
     */
    private function nullUnknownEvents(array $inserts): array
    {
        $ids = collect($inserts)->pluck('event_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return $inserts;
        }

        $valid = Event::query()->whereIn('id', $ids->all())->pluck('id')->all();

        return array_map(function (array $row) use ($valid) {
            if ($row['event_id'] !== null && ! in_array($row['event_id'], $valid, true)) {
                $row['event_id'] = null;
            }

            return $row;
        }, $inserts);
    }

    private function raiseDuration(string $token, int $duration): void
    {
        AnalyticsEvent::query()
            ->where('event_name', AnalyticsEvent::EVENT_PAGE_VIEW)
            ->where('view_token', $token)
            ->where(function ($inner) use ($duration) {
                $inner->whereNull('duration_ms')->orWhere('duration_ms', '<', $duration);
            })
            ->update([
                'duration_ms' => $duration,
                'updated_at' => Carbon::now(),
            ]);
    }

    private function duration(mixed $value): int
    {
        if (! is_numeric($value)) {
            return 0;
        }

        return max(0, min(self::MAX_DURATION_MS, (int) $value));
    }

    private function eventId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function viewToken(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^[A-Za-z0-9]{8,32}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function routeName(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^[A-Za-z0-9._\-]{1,80}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function ip(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return mb_substr($ip, 0, 45);
    }

    public function sanitizeLabel(?string $label): ?string
    {
        if ($label === null) {
            return null;
        }

        $label = mb_substr($label, 0, 200);
        $label = trim((string) preg_replace('/\s+/u', ' ', $label));
        if ($label === '') {
            return null;
        }

        $label = (string) preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', '[correo]', $label);
        $label = (string) preg_replace('/\d{6,}/', '•••', $label);

        return mb_substr($label, 0, 80);
    }

    public function sanitizePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = trim(mb_substr($path, 0, 500));
        if ($path === '') {
            return null;
        }

        if (str_contains($path, '://')) {
            $path = (string) (parse_url($path, PHP_URL_PATH) ?: '/');
        }

        $path = explode('?', $path, 2)[0];
        $path = explode('#', $path, 2)[0];
        if (str_contains($path, '..')) {
            return null;
        }

        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        $segments = array_map(function (string $segment) {
            if ($segment !== '' && preg_match('/^[A-Za-z0-9]{24,}$/', $segment)) {
                return '[dato]';
            }

            return mb_substr($segment, 0, 80);
        }, explode('/', $path));

        return mb_substr(implode('/', $segments), 0, 180);
    }

    private function sanitizeReferrer(?string $referrer): ?string
    {
        if ($referrer === null || trim($referrer) === '') {
            return null;
        }

        $referrer = mb_substr(trim($referrer), 0, 500);
        $parts = parse_url($referrer);
        if (! is_array($parts) || empty($parts['host'])) {
            return $this->sanitizePath($referrer);
        }

        $scheme = ($parts['scheme'] ?? 'https') === 'http' ? 'http' : 'https';
        $path = $this->sanitizePath((string) ($parts['path'] ?? '/')) ?? '/';

        return mb_substr($scheme.'://'.$parts['host'].$path, 0, 180);
    }

    private function deviceType(string $userAgent): string
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
