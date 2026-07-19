<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Seat;
use App\Models\Section;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueLayoutElement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueLayoutWysiwygTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_layout_elements_for_venue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$venue, $seat] = $this->createVenueWithSeat();

        $payload = [
            'elements' => [
                [
                    'type' => 'stage',
                    'x' => 100,
                    'y' => 40,
                    'w' => 180,
                    'h' => 50,
                    'rotation' => 0,
                    'z_index' => 1,
                    'meta' => ['label' => 'ESCENARIO'],
                ],
                [
                    'type' => 'table',
                    'x' => 320,
                    'y' => 120,
                    'w' => 120,
                    'h' => 72,
                    'rotation' => 0,
                    'z_index' => 2,
                    'meta' => ['label' => 'MESA'],
                ],
                [
                    'type' => 'seat',
                    'seat_id' => $seat->id,
                    'x' => 120,
                    'y' => 180,
                    'w' => 48,
                    'h' => 48,
                    'rotation' => 0,
                    'z_index' => 3,
                    'meta' => [],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->putJson(route('admin.venues.layout.save', $venue), $payload + [
                'canvas_width' => 1100,
                'canvas_height' => 720,
            ])
            ->assertOk()
            ->assertJsonPath('elements.0.type', 'stage')
            ->assertJsonPath('elements.1.type', 'table')
            ->assertJsonPath('canvas_width', 1100)
            ->assertJsonPath('canvas_height', 720);

        $this->assertDatabaseHas('venue_layout_elements', [
            'venue_id' => $venue->id,
            'type' => 'seat',
            'seat_id' => $seat->id,
        ]);

        $this->assertDatabaseHas('venue_layout_elements', [
            'venue_id' => $venue->id,
            'type' => 'table',
            'seat_id' => null,
        ]);

        $venue->refresh();
        $this->assertSame(1100, $venue->layout_canvas_width);
        $this->assertSame(720, $venue->layout_canvas_height);
    }

    public function test_admin_can_save_wide_stage_element(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$venue, $seat] = $this->createVenueWithSeat();

        $this->actingAs($admin)
            ->putJson(route('admin.venues.layout.save', $venue), [
                'elements' => [
                    ['type' => 'stage', 'x' => 80, 'y' => 48, 'w' => 1008, 'h' => 64, 'rotation' => 0, 'z_index' => 1, 'meta' => []],
                    ['type' => 'seat', 'seat_id' => $seat->id, 'x' => 16, 'y' => 192, 'w' => 48, 'h' => 48, 'z_index' => 2],
                ],
                'canvas_width' => 1600,
                'canvas_height' => 760,
            ])
            ->assertOk()
            ->assertJsonPath('elements.0.type', 'stage');

        $this->assertDatabaseHas('venue_layout_elements', [
            'venue_id' => $venue->id,
            'type' => 'stage',
            'w' => 1008,
        ]);
    }

    public function test_admin_cannot_save_duplicate_seat_in_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$venue, $seat] = $this->createVenueWithSeat();

        $this->actingAs($admin)
            ->putJson(route('admin.venues.layout.save', $venue), [
                'elements' => [
                    ['type' => 'seat', 'seat_id' => $seat->id, 'x' => 0, 'y' => 0, 'w' => 48, 'h' => 48, 'z_index' => 1],
                    ['type' => 'seat', 'seat_id' => $seat->id, 'x' => 60, 'y' => 0, 'w' => 48, 'h' => 48, 'z_index' => 2],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_re_adding_a_persisted_seat_as_new_element_does_not_violate_unique_constraint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$venue, $seat] = $this->createVenueWithSeat();

        $existing = $venue->layoutElements()->create([
            'type' => 'seat',
            'seat_id' => $seat->id,
            'x' => 10,
            'y' => 20,
            'w' => 48,
            'h' => 48,
            'rotation' => 0,
            'z_index' => 1,
            'meta' => [],
        ]);

        // La butaca se reenvía como elemento nuevo (id local negativo) sin su id
        // persistido; la fila antigua debe eliminarse antes de recrearla.
        $this->actingAs($admin)
            ->putJson(route('admin.venues.layout.save', $venue), [
                'elements' => [
                    ['id' => -1, 'type' => 'seat', 'seat_id' => $seat->id, 'x' => 120, 'y' => 180, 'w' => 48, 'h' => 48, 'z_index' => 1],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('venue_layout_elements', ['id' => $existing->id]);
        $this->assertSame(1, $venue->layoutElements()->where('seat_id', $seat->id)->count());
    }

    public function test_client_seats_endpoint_includes_layout_elements(): void
    {
        $client = User::factory()->create(['role' => 'user']);
        [$venue, $seat] = $this->createVenueWithSeat();
        $event = $this->createEventForVenue($venue);

        $venue->layoutElements()->create([
            'type' => 'seat',
            'seat_id' => $seat->id,
            'x' => 80,
            'y' => 120,
            'w' => 48,
            'h' => 48,
            'rotation' => 0,
            'z_index' => 1,
            'meta' => [],
        ]);

        $this->actingAs($client)
            ->getJson(route('reservations.seats', $event))
            ->assertOk()
            ->assertJsonPath('layout.0.type', 'seat')
            ->assertJsonPath('layout.0.seat.id', $seat->id)
            ->assertJsonPath('venue.layout_canvas_width', null)
            ->assertJsonPath('venue.layout_canvas_height', null);
    }

    public function test_saving_layout_without_seat_sections_does_not_clear_seat_section_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$venue, $seat] = $this->createVenueWithSeat();
        $section = Section::create([
            'venue_id' => $venue->id,
            'name' => 'VIP',
            'slug' => 'vip',
            'sort_order' => 0,
            'has_seats' => true,
        ]);
        $seat->update(['section_id' => $section->id]);

        $payload = [
            'elements' => [
                [
                    'type' => 'seat',
                    'seat_id' => $seat->id,
                    'x' => 120,
                    'y' => 180,
                    'w' => 48,
                    'h' => 48,
                    'rotation' => 0,
                    'z_index' => 1,
                    'meta' => [],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->putJson(route('admin.venues.layout.save', $venue), $payload)
            ->assertOk();

        $seat->refresh();
        $this->assertSame($section->id, $seat->section_id);
    }

    public function test_saving_layout_with_seat_sections_updates_seat_section_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$venue, $seat] = $this->createVenueWithSeat();
        $section = Section::create([
            'venue_id' => $venue->id,
            'name' => 'Platea',
            'slug' => 'platea',
            'sort_order' => 0,
            'has_seats' => true,
        ]);

        $payload = [
            'elements' => [
                [
                    'type' => 'seat',
                    'seat_id' => $seat->id,
                    'x' => 120,
                    'y' => 180,
                    'w' => 48,
                    'h' => 48,
                    'rotation' => 0,
                    'z_index' => 1,
                    'meta' => [],
                ],
            ],
            'seat_sections' => [
                (string) $seat->id => $section->id,
            ],
        ];

        $this->actingAs($admin)
            ->putJson(route('admin.venues.layout.save', $venue), $payload)
            ->assertOk()
            ->assertJsonPath('seats.0.section_id', $section->id);

        $seat->refresh();
        $this->assertSame($section->id, $seat->section_id);
    }

    public function test_venue_edit_page_embeds_section_id_for_seats_and_layout_elements(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$venue, $seat] = $this->createVenueWithSeat();
        $section = Section::create([
            'venue_id' => $venue->id,
            'name' => 'Palco',
            'slug' => 'palco',
            'sort_order' => 0,
            'has_seats' => true,
        ]);
        $seat->update(['section_id' => $section->id]);

        VenueLayoutElement::create([
            'venue_id' => $venue->id,
            'seat_id' => $seat->id,
            'type' => VenueLayoutElement::TYPE_SEAT,
            'x' => 10,
            'y' => 20,
            'w' => 48,
            'h' => 48,
            'rotation' => 0,
            'z_index' => 1,
            'meta' => null,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.venues.edit', $venue))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"section_id":'.$section->id, $html);
        $this->assertStringContainsString('"seat_id":'.$seat->id, $html);
        $this->assertStringContainsString('data-add-type="table"', $html);
        $this->assertStringContainsString('>Mesa</button>', $html);
    }

    public function test_client_checkout_uses_fallback_when_layout_missing(): void
    {
        $client = User::factory()->create(['role' => 'user']);
        [$venue] = $this->createVenueWithSeat();
        $event = $this->createEventForVenue($venue);

        $this->actingAs($client)
            ->get(route('reservations.create', $event))
            ->assertOk()
            ->assertSee('layoutElements&quot;:[]', false)
            ->assertSee('Elige tus butacas haciendo clic');
    }

    public function test_seller_surrogate_seats_shows_layout_map_like_client_checkout(): void
    {
        $seller = User::factory()->create(['role' => User::ROLE_VENDEDOR]);
        $client = User::factory()->create(['role' => 'user']);
        [$venue, $seat] = $this->createVenueWithSeat();
        $event = $this->createEventForVenue($venue);

        $venue->layoutElements()->create([
            'type' => 'seat',
            'seat_id' => $seat->id,
            'x' => 80,
            'y' => 120,
            'w' => 48,
            'h' => 48,
            'rotation' => 0,
            'z_index' => 1,
            'meta' => [],
        ]);

        $this->withSession([
            'seller_surrogate.client_user_id' => $client->id,
            'seller_surrogate.event_id' => $event->id,
        ]);

        $this->actingAs($seller)
            ->get(route('seller.events.surrogate-sale.seats', $event))
            ->assertOk()
            ->assertSee('Plano del venue', false)
            ->assertSee('layoutZoomResetFitSimple()', false)
            ->assertSee('hasCustomLayout()', false);
    }

    public function test_seller_can_view_readonly_event_seat_map(): void
    {
        $seller = User::factory()->create(['role' => User::ROLE_VENDEDOR]);
        [$venue, $seat] = $this->createVenueWithSeat();
        $event = $this->createEventForVenue($venue);

        $venue->layoutElements()->create([
            'type' => 'seat',
            'seat_id' => $seat->id,
            'x' => 80,
            'y' => 120,
            'w' => 48,
            'h' => 48,
            'rotation' => 0,
            'z_index' => 1,
            'meta' => [],
        ]);

        $backUrl = route('seller.events.index');

        $this->actingAs($seller)
            ->get(route('seller.events.seats', $event))
            ->assertOk()
            ->assertSee('Mapa de butacas', false)
            ->assertSee('Plano del venue', false)
            ->assertSee('Mismo plano que en el checkout del cliente', false)
            ->assertSee('layoutZoomResetFit()', false)
            ->assertSee('&quot;readonly&quot;:true', false)
            ->assertSee('Volver a eventos', false)
            ->assertSee('href="'.$backUrl.'"', false)
            ->assertSee('sticky top-20 z-50', false)
            ->assertSee('pointer-events-auto', false)
            ->assertSee('Navegación del mapa de butacas', false);
    }

    public function test_seller_seat_map_requires_seller_role(): void
    {
        $client = User::factory()->create(['role' => 'user']);
        [$venue] = $this->createVenueWithSeat();
        $event = $this->createEventForVenue($venue);

        $this->actingAs($client)
            ->get(route('seller.events.seats', $event))
            ->assertForbidden();
    }

    public function test_seller_seat_map_redirects_when_event_has_no_venue(): void
    {
        $seller = User::factory()->create(['role' => User::ROLE_VENDEDOR]);
        $event = Event::create([
            'name' => 'Sin venue',
            'description' => 'Descripcion',
            'starts_at' => now()->addDay(),
            'venue' => 'Sala X',
            'venue_id' => null,
            'payment_code_prefix' => 'EVT',
            'is_active' => true,
        ]);

        $this->actingAs($seller)
            ->get(route('seller.events.seats', $event))
            ->assertRedirect(route('seller.events.index'))
            ->assertSessionHas('error');
    }

    private function createVenueWithSeat(): array
    {
        $venue = Venue::create([
            'name' => 'Venue Layout Test',
            'slug' => 'venue-layout-test',
            'address' => 'Direccion 123',
            'seat_rows' => 5,
            'seat_columns' => 5,
        ]);

        $seat = Seat::create([
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 1,
            'label' => 'A-1',
            'blocked' => false,
        ]);

        return [$venue, $seat];
    }

    private function createEventForVenue(Venue $venue): Event
    {
        return Event::create([
            'name' => 'Evento Layout Test',
            'description' => 'Descripcion',
            'starts_at' => now()->addDay(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'EVT',
            'is_active' => true,
        ]);
    }
}
