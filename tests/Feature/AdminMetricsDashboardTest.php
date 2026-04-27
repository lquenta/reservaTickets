<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMetricsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_metrics_kpis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'user']);
        $event = Event::create([
            'name' => 'Evento metricas',
            'starts_at' => now()->addDays(2),
            'venue' => 'Venue test',
            'payment_code_prefix' => 'MET',
            'is_active' => true,
        ]);

        $reservation = Reservation::create([
            'user_id' => $buyer->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'MTR-001',
        ]);

        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'holder_name' => 'Cliente Demo',
            'position' => 1,
            'validated_at' => now(),
        ]);

        AnalyticsEvent::create([
            'event_name' => AnalyticsEvent::EVENT_VIEW_EVENT,
            'session_id' => 'session-a',
            'user_id' => $buyer->id,
            'event_id' => $event->id,
            'path' => '/events',
            'referrer' => null,
            'device_type' => 'desktop',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard de métricas')
            ->assertSee('Visitas')
            ->assertSee('Conversiones');
    }

    public function test_metrics_report_route_is_admin_only(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.reports.metrics'))
            ->assertForbidden();
    }

    public function test_metrics_report_displays_ip_activity_last_10_days(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::create([
            'name' => 'Evento IP',
            'starts_at' => now()->addDays(2),
            'venue' => 'Venue test',
            'payment_code_prefix' => 'MIP',
            'is_active' => true,
        ]);

        AnalyticsEvent::create([
            'event_name' => AnalyticsEvent::EVENT_VIEW_EVENT,
            'session_id' => 'session-ip-1',
            'user_id' => null,
            'event_id' => $event->id,
            'ip_address' => '200.10.10.1',
            'path' => '/events',
            'referrer' => null,
            'device_type' => 'desktop',
            'occurred_at' => now()->subDays(1),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.metrics'))
            ->assertOk()
            ->assertSee('Detalle de visitas por IP')
            ->assertSee('200.10.10.1');
    }
}
