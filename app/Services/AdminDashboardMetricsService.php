<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardMetricsService
{
    public function normalizeFilters(array $filters): array
    {
        $today = now();
        $dateTo = isset($filters['date_to']) && $filters['date_to'] ? Carbon::parse((string) $filters['date_to'])->endOfDay() : $today->copy()->endOfDay();
        $dateFrom = isset($filters['date_from']) && $filters['date_from'] ? Carbon::parse((string) $filters['date_from'])->startOfDay() : $today->copy()->subDays(29)->startOfDay();
        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $eventScope = ($filters['event_scope'] ?? 'active') === 'all' ? 'all' : 'active';
        $eventId = isset($filters['event_id']) ? (int) $filters['event_id'] : 0;

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'event_scope' => $eventScope,
            'event_id' => $eventId > 0 ? $eventId : null,
        ];
    }

    public function eventsForFilter(string $eventScope): Collection
    {
        return Event::query()
            ->when($eventScope !== 'all', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('starts_at')
            ->get(['id', 'name', 'starts_at', 'is_active']);
    }

    public function build(array $filters): array
    {
        $eventIds = $this->resolveEventIds($filters);
        $hasSpecificEventFilter = ! empty($filters['event_id']);

        $baseAnalytics = AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$filters['date_from'], $filters['date_to']])
            ->when($hasSpecificEventFilter && ! empty($eventIds), fn ($q) => $q->whereIn('event_id', $eventIds));

        $visitEvents = (clone $baseAnalytics)->where('event_name', AnalyticsEvent::EVENT_VIEW_EVENT);
        $visits = (clone $visitEvents)->distinct('session_id')->count('session_id');
        if ($visits === 0) {
            $visits = (clone $visitEvents)->count();
        }

        $confirmedReservations = Reservation::query()
            ->where('status', Reservation::STATUS_CONFIRMADO)
            ->whereBetween('created_at', [$filters['date_from'], $filters['date_to']])
            ->when(! empty($eventIds), fn ($q) => $q->whereIn('event_id', $eventIds));

        $conversions = (clone $confirmedReservations)->count();

        $pendingReservations = Reservation::query()
            ->whereIn('status', [Reservation::STATUS_INICIADO, Reservation::STATUS_PENDIENTE_PAGO])
            ->whereBetween('created_at', [$filters['date_from'], $filters['date_to']])
            ->when(! empty($eventIds), fn ($q) => $q->whereIn('event_id', $eventIds))
            ->count();

        $confirmedAudience = ReservationTicket::query()
            ->whereHas('reservation', function ($q) use ($filters, $eventIds) {
                $q->where('status', Reservation::STATUS_CONFIRMADO)
                    ->whereBetween('created_at', [$filters['date_from'], $filters['date_to']])
                    ->when(! empty($eventIds), fn ($inner) => $inner->whereIn('event_id', $eventIds));
            })
            ->count();

        $attendanceConfirmed = ReservationTicket::query()
            ->whereNotNull('validated_at')
            ->whereBetween('validated_at', [$filters['date_from'], $filters['date_to']])
            ->whereHas('reservation', fn ($q) => $q->when(! empty($eventIds), fn ($inner) => $inner->whereIn('event_id', $eventIds)))
            ->count();

        $salesByEvent = $this->salesByEvent($filters, $eventIds);
        $salesTotal = (float) $salesByEvent->sum('total');

        return [
            'filters' => $filters,
            'kpis' => [
                'visits' => $visits,
                'conversions' => $conversions,
                'conversion_rate' => $visits > 0 ? ($conversions / $visits) * 100 : 0.0,
                'sales_total' => $salesTotal,
                'confirmed_audience' => $confirmedAudience,
                'reserved_pending' => $pendingReservations,
                'attendance_confirmed' => $attendanceConfirmed,
            ],
            'trend' => $this->buildTrend($filters, $eventIds),
            'events_table' => $this->buildEventsTable($filters, $eventIds, $salesByEvent),
            'ip_activity_last_10_days' => $this->buildIpActivityLast10Days($eventIds, $hasSpecificEventFilter),
            'sales_by_event' => $salesByEvent,
            'alerts' => $this->buildAlerts($conversions, $pendingReservations, $visits),
        ];
    }

    private function resolveEventIds(array $filters): array
    {
        if ($filters['event_id']) {
            return [$filters['event_id']];
        }

        return Event::query()
            ->when($filters['event_scope'] !== 'all', fn ($q) => $q->where('is_active', true))
            ->pluck('id')
            ->all();
    }

    private function buildTrend(array $filters, array $eventIds): Collection
    {
        $period = collect();
        $cursor = $filters['date_from']->copy();
        while ($cursor->lte($filters['date_to'])) {
            $period->push($cursor->toDateString());
            $cursor->addDay();
        }

        $visits = AnalyticsEvent::query()
            ->where('event_name', AnalyticsEvent::EVENT_VIEW_EVENT)
            ->whereBetween('occurred_at', [$filters['date_from'], $filters['date_to']])
            ->when(! empty($eventIds), fn ($q) => $q->whereIn('event_id', $eventIds))
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $conversions = Reservation::query()
            ->where('status', Reservation::STATUS_CONFIRMADO)
            ->whereBetween('created_at', [$filters['date_from'], $filters['date_to']])
            ->when(! empty($eventIds), fn ($q) => $q->whereIn('event_id', $eventIds))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $salesByDay = ReservationTicket::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_tickets.reservation_id')
            ->leftJoin('events', 'events.id', '=', 'reservations.event_id')
            ->leftJoin('ticket_templates', 'ticket_templates.event_id', '=', 'events.id')
            ->where('reservations.status', Reservation::STATUS_CONFIRMADO)
            ->whereBetween('reservations.created_at', [$filters['date_from'], $filters['date_to']])
            ->when(! empty($eventIds), fn ($q) => $q->whereIn('reservations.event_id', $eventIds))
            ->selectRaw('DATE(reservations.created_at) as day, SUM(COALESCE(ticket_templates.price,0)) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $period->map(fn ($day) => [
            'day' => $day,
            'visits' => (int) ($visits[$day] ?? 0),
            'conversions' => (int) ($conversions[$day] ?? 0),
            'sales' => (float) ($salesByDay[$day] ?? 0),
        ]);
    }

    private function buildEventsTable(array $filters, array $eventIds, Collection $salesByEvent): Collection
    {
        if (empty($eventIds)) {
            return collect();
        }

        $events = Event::query()->whereIn('id', $eventIds)->get(['id', 'name', 'is_active'])->keyBy('id');

        $visitsByEvent = AnalyticsEvent::query()
            ->where('event_name', AnalyticsEvent::EVENT_VIEW_EVENT)
            ->whereBetween('occurred_at', [$filters['date_from'], $filters['date_to']])
            ->whereIn('event_id', $eventIds)
            ->select('event_id', DB::raw('COUNT(*) as total'))
            ->groupBy('event_id')
            ->pluck('total', 'event_id');

        $conversionsByEvent = Reservation::query()
            ->where('status', Reservation::STATUS_CONFIRMADO)
            ->whereBetween('created_at', [$filters['date_from'], $filters['date_to']])
            ->whereIn('event_id', $eventIds)
            ->select('event_id', DB::raw('COUNT(*) as total'))
            ->groupBy('event_id')
            ->pluck('total', 'event_id');

        $confirmedAudienceByEvent = ReservationTicket::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_tickets.reservation_id')
            ->where('reservations.status', Reservation::STATUS_CONFIRMADO)
            ->whereBetween('reservations.created_at', [$filters['date_from'], $filters['date_to']])
            ->whereIn('reservations.event_id', $eventIds)
            ->select('reservations.event_id', DB::raw('COUNT(*) as total'))
            ->groupBy('reservations.event_id')
            ->pluck('total', 'event_id');

        return collect($eventIds)->map(function ($eventId) use ($events, $visitsByEvent, $conversionsByEvent, $confirmedAudienceByEvent, $salesByEvent) {
            $sales = (float) ($salesByEvent->firstWhere('event_id', (int) $eventId)->total ?? 0);
            $visits = (int) ($visitsByEvent[$eventId] ?? 0);
            $conversions = (int) ($conversionsByEvent[$eventId] ?? 0);

            return (object) [
                'event_id' => (int) $eventId,
                'event_name' => $events[$eventId]->name ?? ('Evento #'.$eventId),
                'is_active' => (bool) ($events[$eventId]->is_active ?? false),
                'visits' => $visits,
                'conversions' => $conversions,
                'conversion_rate' => $visits > 0 ? ($conversions / $visits) * 100 : 0,
                'sales_total' => $sales,
                'confirmed_audience' => (int) ($confirmedAudienceByEvent[$eventId] ?? 0),
            ];
        })->sortByDesc('sales_total')->values();
    }

    private function salesByEvent(array $filters, array $eventIds): Collection
    {
        if (empty($eventIds)) {
            return collect();
        }

        $ticketsByEvent = ReservationTicket::query()
            ->join('reservations', 'reservation_tickets.reservation_id', '=', 'reservations.id')
            ->where('reservations.status', Reservation::STATUS_CONFIRMADO)
            ->whereBetween('reservations.created_at', [$filters['date_from'], $filters['date_to']])
            ->whereIn('reservations.event_id', $eventIds)
            ->select('reservations.event_id', DB::raw('COUNT(*) as tickets_sold'))
            ->groupBy('reservations.event_id')
            ->get();

        $eventsWithPrice = Event::with(['sections', 'ticketTemplate'])->whereIn('id', $ticketsByEvent->pluck('event_id'))->get()->keyBy('id');

        $confirmedTickets = ReservationTicket::query()
            ->whereHas('reservation', function ($q) use ($filters, $eventIds) {
                $q->where('status', Reservation::STATUS_CONFIRMADO)
                    ->whereBetween('created_at', [$filters['date_from'], $filters['date_to']])
                    ->whereIn('event_id', $eventIds);
            })
            ->with(['seat', 'reservation' => fn ($q) => $q->select('id', 'event_id')->with(['event' => fn ($inner) => $inner->with('sections')])])
            ->get();

        return $ticketsByEvent->map(function ($row) use ($eventsWithPrice, $confirmedTickets) {
            $event = $eventsWithPrice->get($row->event_id);
            $ticketsSold = (int) $row->tickets_sold;
            $total = 0.0;
            $unitPrice = 0.0;

            if ($event && $event->hasSections()) {
                $eventTickets = $confirmedTickets->filter(fn ($t) => $t->reservation && (int) $t->reservation->event_id === (int) $row->event_id);
                foreach ($eventTickets as $ticket) {
                    $eventSection = null;
                    if ($ticket->seat && $ticket->seat->section_id) {
                        $eventSection = $event->sections->firstWhere('id', $ticket->seat->section_id);
                    } elseif ($ticket->section_id) {
                        $eventSection = $event->sections->firstWhere('id', $ticket->section_id);
                    }
                    if ($eventSection && $eventSection->pivot && $eventSection->pivot->price !== null) {
                        $total += (float) $eventSection->pivot->price;
                    }
                }
                $unitPrice = $ticketsSold > 0 ? $total / $ticketsSold : 0.0;
            } else {
                $unitPrice = $event && $event->ticketTemplate ? (float) $event->ticketTemplate->price : 0.0;
                $total = $ticketsSold * $unitPrice;
            }

            return (object) [
                'event_id' => (int) $row->event_id,
                'event_name' => $event ? $event->name : 'Evento #'.$row->event_id,
                'tickets_sold' => $ticketsSold,
                'unit_price' => $unitPrice,
                'total' => $total,
            ];
        })->values();
    }

    private function buildAlerts(int $conversions, int $pendingReservations, int $visits): array
    {
        $lowConversion = $visits >= 20 && ($conversions / max(1, $visits)) < 0.05;

        return [
            'high_pending' => $pendingReservations >= 10,
            'low_conversion' => $lowConversion,
        ];
    }

    private function buildIpActivityLast10Days(array $eventIds, bool $hasSpecificEventFilter): Collection
    {
        $from = now()->subDays(9)->startOfDay();
        $to = now()->endOfDay();

        $rows = AnalyticsEvent::query()
            ->where('event_name', AnalyticsEvent::EVENT_VIEW_EVENT)
            ->whereBetween('occurred_at', [$from, $to])
            ->whereNotNull('ip_address')
            ->where('ip_address', '!=', '')
            ->when($hasSpecificEventFilter && ! empty($eventIds), fn ($q) => $q->whereIn('event_id', $eventIds))
            ->selectRaw('ip_address, DATE(occurred_at) as day, COUNT(*) as total')
            ->groupBy('ip_address', 'day')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        return $rows
            ->groupBy('ip_address')
            ->map(function (Collection $ipRows, string $ip) {
                $visitsTotal = (int) $ipRows->sum('total');
                $daily = $ipRows
                    ->sortByDesc('day')
                    ->map(fn ($row) => [
                        'day' => (string) $row->day,
                        'total' => (int) $row->total,
                    ])
                    ->values();

                return (object) [
                    'ip_address' => $ip,
                    'visits_total' => $visitsTotal,
                    'last_day' => $daily->first()['day'] ?? null,
                    'daily' => $daily,
                ];
            })
            ->sortByDesc('visits_total')
            ->values();
    }
}
