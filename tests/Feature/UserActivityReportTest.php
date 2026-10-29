<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Event;
use App\Models\User;
use App\Services\AnalyticsIngestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_records_page_and_click_without_ip_or_typed_secrets(): void
    {
        $user = User::factory()->create(['name' => 'Ana Visita']);

        $this->actingAs($user)->postJson(route('analytics.events.store'), [
            'events' => [
                [
                    'event_name' => 'page_view',
                    'path' => '/reset-password/abcdefghijklmnopqrstuvwxyz123456?token=secreto',
                    'referrer' => 'https://example.test/cartelera?utm=1',
                    'route_name' => 'password.reset',
                    'view_token' => 'abcd1234abcd1234',
                    'duration_ms' => 999999999,
                    'label' => 'Avisar a ana@nova.test al 64066996',
                ],
                [
                    'event_name' => 'click',
                    'path' => '/events',
                    'route_name' => 'events.index',
                    'label' => 'Reservarse',
                    'view_token' => 'abcd1234abcd1234',
                ],
                [
                    'event_name' => 'purchase',
                    'path' => '/checkout/1',
                ],
            ],
        ])->assertNoContent();

        $page = AnalyticsEvent::query()->where('event_name', 'page_view')->first();
        $this->assertNotNull($page);
        $this->assertSame($user->id, $page->user_id);
        $this->assertNull($page->ip_address);
        $this->assertSame('/reset-password/[dato]', $page->path);
        $this->assertSame('https://example.test/cartelera', $page->referrer);
        $this->assertSame(AnalyticsIngestService::MAX_DURATION_MS, $page->duration_ms);
        $this->assertSame('Avisar a [correo] al •••', $page->label);
        $this->assertSame('password.reset', $page->route_name);

        $this->assertDatabaseHas('analytics_events', [
            'event_name' => 'click',
            'label' => 'Reservarse',
            'path' => '/events',
            'ip_address' => null,
        ]);
        $this->assertDatabaseMissing('analytics_events', [
            'event_name' => 'purchase',
        ]);
    }

    public function test_duration_updates_in_place_and_never_goes_backwards(): void
    {
        $this->postJson(route('analytics.events.store'), [
            'events' => [[
                'event_name' => 'page_view',
                'path' => '/events',
                'view_token' => 'token1234token1234',
                'duration_ms' => 4000,
            ]],
        ])->assertNoContent();

        $this->postJson(route('analytics.events.store'), [
            'events' => [[
                'event_name' => 'page_duration',
                'view_token' => 'token1234token1234',
                'duration_ms' => 22000,
            ]],
        ])->assertNoContent();

        $this->assertSame(1, AnalyticsEvent::query()->where('event_name', 'page_view')->count());
        $this->assertSame(22000, AnalyticsEvent::query()->where('event_name', 'page_view')->value('duration_ms'));

        $this->postJson(route('analytics.events.store'), [
            'events' => [[
                'event_name' => 'page_duration',
                'view_token' => 'token1234token1234',
                'duration_ms' => 1000,
            ]],
        ])->assertNoContent();

        $this->assertSame(22000, AnalyticsEvent::query()->where('event_name', 'page_view')->value('duration_ms'));
        $this->assertSame(0, AnalyticsEvent::query()->where('event_name', 'page_duration')->count());
    }

    public function test_funnel_event_keeps_ip_and_linked_event(): void
    {
        $event = Event::create([
            'name' => 'Noche NOVA',
            'starts_at' => now()->addDay(),
            'venue' => 'Sala',
            'payment_code_prefix' => 'NV',
            'is_active' => true,
        ]);

        $this->postJson(route('analytics.events.store'), [
            'event_name' => 'view_event',
            'event_id' => $event->id,
            'path' => '/events',
        ])->assertNoContent();

        $row = AnalyticsEvent::query()->where('event_name', 'view_event')->first();
        $this->assertNotNull($row);
        $this->assertSame($event->id, $row->event_id);
        $this->assertNotNull($row->ip_address);
    }

    public function test_oversized_batch_is_rejected(): void
    {
        $events = array_fill(0, 26, [
            'event_name' => 'click',
            'path' => '/events',
            'label' => 'Eventos',
        ]);

        $this->postJson(route('analytics.events.store'), ['events' => $events])->assertStatus(422);
        $this->assertDatabaseCount('analytics_events', 0);
    }

    public function test_admin_can_read_the_activity_report_and_others_cannot(): void
    {
        $visitor = User::factory()->create([
            'name' => 'Lucía Recorrido',
            'email' => 'lucia.recorrido@example.test',
        ]);

        $this->actingAs($visitor)->postJson(route('analytics.events.store'), [
            'events' => [
                [
                    'event_name' => 'page_view',
                    'path' => '/events',
                    'route_name' => 'events.index',
                    'view_token' => 'page1234page1234',
                    'duration_ms' => 125000,
                ],
                [
                    'event_name' => 'click',
                    'path' => '/events',
                    'route_name' => 'events.index',
                    'label' => 'Reservarse',
                ],
            ],
        ])->assertNoContent();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.reports.activity', ['q' => 'Lucía']))
            ->assertOk()
            ->assertSee('Lucía Recorrido')
            ->assertSee('Reservarse')
            ->assertSee('Cartelera')
            ->assertSee('2 min 5 s');

        $this->actingAs($admin)
            ->get(route('admin.reports.activity', ['q' => 'Lucía', 'kind' => 'actions']))
            ->assertOk()
            ->assertSee('Reservarse')
            ->assertDontSee('Vio la página');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.activity'))
            ->assertForbidden();

        $this->actingAsGuest()
            ->get(route('admin.reports.activity'))
            ->assertRedirect(route('login'));
    }
}
