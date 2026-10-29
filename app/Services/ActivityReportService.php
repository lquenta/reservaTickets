<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ActivityReportService
{
    /**
     * @return array{date_from: Carbon, date_to: Carbon, q: string, path: string, kind: string, clamped: bool}
     */
    public function filters(Request $request): array
    {
        $dateTo = $request->filled('date_to')
            ? Carbon::parse((string) $request->input('date_to'))->endOfDay()
            : now()->endOfDay();
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse((string) $request->input('date_from'))->startOfDay()
            : $dateTo->copy()->subDays(6)->startOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $clamped = $dateFrom->copy()->startOfDay()->diffInDays($dateTo->copy()->startOfDay(), true) > 31;
        if ($clamped) {
            $dateFrom = $dateTo->copy()->subDays(31)->startOfDay();
        }

        $kind = (string) $request->input('kind', 'all');
        if (! in_array($kind, ['all', 'pages', 'actions'], true)) {
            $kind = 'all';
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'q' => trim(mb_substr((string) $request->input('q', ''), 0, 120)),
            'path' => trim(mb_substr((string) $request->input('path', ''), 0, 180)),
            'kind' => $kind,
            'clamped' => $clamped,
        ];
    }

    /**
     * @param  array{date_from: Carbon, date_to: Carbon, q: string, path: string, kind: string, clamped: bool}  $filters
     * @return array{page_views: int, actions: int, sessions: int, users: int, avg_ms: ?int}
     */
    public function summary(array $filters): array
    {
        $actionList = implode(',', array_fill(0, count(AnalyticsEvent::ACTION_NAMES), '?'));
        $row = $this->base($filters)->selectRaw(
            "SUM(CASE WHEN event_name = ? THEN 1 ELSE 0 END) as page_views,
             SUM(CASE WHEN event_name IN ($actionList) THEN 1 ELSE 0 END) as actions,
             COUNT(DISTINCT session_id) as sessions,
             COUNT(DISTINCT user_id) as users,
             AVG(CASE WHEN event_name = ? AND duration_ms > 0 THEN duration_ms END) as avg_ms",
            array_merge(
                [AnalyticsEvent::EVENT_PAGE_VIEW],
                AnalyticsEvent::ACTION_NAMES,
                [AnalyticsEvent::EVENT_PAGE_VIEW]
            )
        )->first();

        return [
            'page_views' => (int) ($row->page_views ?? 0),
            'actions' => (int) ($row->actions ?? 0),
            'sessions' => (int) ($row->sessions ?? 0),
            'users' => (int) ($row->users ?? 0),
            'avg_ms' => $row?->avg_ms !== null ? (int) round((float) $row->avg_ms) : null,
        ];
    }

    /**
     * @param  array{date_from: Carbon, date_to: Carbon, q: string, path: string, kind: string, clamped: bool}  $filters
     */
    public function topPages(array $filters): Collection
    {
        return $this->base($filters)
            ->where('event_name', AnalyticsEvent::EVENT_PAGE_VIEW)
            ->selectRaw('path, MAX(route_name) as route_name, COUNT(*) as views, AVG(duration_ms) as avg_ms')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(8)
            ->get();
    }

    /**
     * @param  array{date_from: Carbon, date_to: Carbon, q: string, path: string, kind: string, clamped: bool}  $filters
     */
    public function actors(array $filters): Collection
    {
        $actionList = implode(',', array_fill(0, count(AnalyticsEvent::ACTION_NAMES), '?'));
        $rows = $this->base($filters)->selectRaw(
            "user_id,
             CASE WHEN user_id IS NULL THEN session_id ELSE '' END as guest_key,
             SUM(CASE WHEN event_name = ? THEN 1 ELSE 0 END) as pages,
             SUM(CASE WHEN event_name IN ($actionList) THEN 1 ELSE 0 END) as actions,
             SUM(CASE WHEN event_name = ? THEN COALESCE(duration_ms, 0) ELSE 0 END) as visible_ms",
            array_merge(
                [AnalyticsEvent::EVENT_PAGE_VIEW],
                AnalyticsEvent::ACTION_NAMES,
                [AnalyticsEvent::EVENT_PAGE_VIEW]
            )
        )
            ->groupByRaw("user_id, CASE WHEN user_id IS NULL THEN session_id ELSE '' END")
            ->orderByDesc('visible_ms')
            ->limit(40)
            ->get();

        $users = User::query()
            ->whereIn('id', $rows->pluck('user_id')->filter()->all())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $user = $row->user_id ? $users->get($row->user_id) : null;
            $tail = substr((string) ($row->guest_key ?? ''), -6);

            return (object) [
                'name' => $user?->name ?? ($tail !== '' ? 'Invitado · '.$tail : 'Invitado'),
                'email' => $user?->email,
                'pages' => (int) $row->pages,
                'actions' => (int) $row->actions,
                'visible_ms' => (int) $row->visible_ms,
            ];
        });
    }

    /**
     * @param  array{date_from: Carbon, date_to: Carbon, q: string, path: string, kind: string, clamped: bool}  $filters
     */
    public function detail(array $filters)
    {
        return $this->detailQuery($filters)
            ->with(['user:id,name,email', 'trackedEvent:id,name'])
            ->paginate(40)
            ->withQueryString();
    }

    /**
     * @param  array{date_from: Carbon, date_to: Carbon, q: string, path: string, kind: string, clamped: bool}  $filters
     */
    public function detailQuery(array $filters): Builder
    {
        return $this->base($filters, withKind: true)
            ->select([
                'id',
                'event_name',
                'session_id',
                'user_id',
                'event_id',
                'path',
                'referrer',
                'label',
                'route_name',
                'duration_ms',
                'device_type',
                'occurred_at',
            ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    /**
     * @param  array{date_from: Carbon, date_to: Carbon, q: string, path: string, kind: string, clamped: bool}  $filters
     */
    private function base(array $filters, bool $withKind = false): Builder
    {
        $query = AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$filters['date_from'], $filters['date_to']]);

        if ($filters['q'] !== '') {
            $like = '%'.addcslashes($filters['q'], '%_\\').'%';
            $query->where(function (Builder $outer) use ($like, $filters) {
                $outer->whereHas('user', function (Builder $user) use ($like) {
                    $user->where('name', 'like', $like)->orWhere('email', 'like', $like);
                });
                if (str_contains(mb_strtolower($filters['q']), 'invit')) {
                    $outer->orWhereNull('user_id');
                }
            });
        }

        if ($filters['path'] !== '') {
            $query->where('path', 'like', '%'.addcslashes($filters['path'], '%_\\').'%');
        }

        if ($withKind && $filters['kind'] === 'pages') {
            $query->where('event_name', AnalyticsEvent::EVENT_PAGE_VIEW);
        }

        if ($withKind && $filters['kind'] === 'actions') {
            $query->whereIn('event_name', AnalyticsEvent::ACTION_NAMES);
        }

        return $query;
    }
}
