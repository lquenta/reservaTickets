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
                    'type' => 'seat',
                    'seat_id' => $seat->id,
                    'x' => 120,
                    'y' => 180,
                    'w' => 48,
                    'h' => 48,
                    'rotation' => 0,
                    'z_index' => 2,
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
            ->assertJsonPath('canvas_width', 1100)
            ->assertJsonPath('canvas_height', 720);

        $this->assertDatabaseHas('venue_layout_elements', [
            'venue_id' => $venue->id,
            'type' => 'seat',
            'seat_id' => $seat->id,
        ]);

        $venue->refresh();
        $this->assertSame(1100, $venue->layout_canvas_width);
        $this->assertSame(720, $venue->layout_canvas_height);
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
