<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\Seat;
use App\Models\Section;
use App\Models\User;
use App\Models\Venue;
use App\Services\SeatLabelPdfService;
use App\Support\SeatLabelLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeatLabelsPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_seat_label_builder(): void
    {
        [$event] = $this->createEventWithSeats(3);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.events.show', $event))
            ->assertOk()
            ->assertSee('Etiquetas de asientos', false);

        $this->actingAs($admin)
            ->get(route('admin.events.seat-labels.create', $event))
            ->assertOk()
            ->assertSee('Etiquetas de asientos', false)
            ->assertSee('Etiquetas por hoja', false)
            ->assertSee('A1', false);
    }

    public function test_builder_is_admin_only(): void
    {
        [$event] = $this->createEventWithSeats(1);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('admin.events.seat-labels.create', $event))
            ->assertForbidden();
    }

    public function test_event_without_venue_is_redirected(): void
    {
        $event = Event::create([
            'name' => 'Sin lugar',
            'starts_at' => now()->addWeek(),
            'venue' => 'Sala externa',
            'venue_id' => null,
            'payment_code_prefix' => 'EXT',
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.events.seat-labels.create', $event))
            ->assertRedirect(route('admin.events.show', $event));
    }

    public function test_pdf_includes_used_and_unused_seats(): void
    {
        [$event, $seats, $section] = $this->createEventWithSeats(5);
        $this->occupySeat($event, $seats[0]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.events.seat-labels.pdf', [
            'event' => $event,
            'per_page' => 4,
            'overlay_opacity' => 20,
            'color_mode' => 'sector',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $data = app(SeatLabelPdfService::class)->buildPdfData($event, [
            'per_page' => 4,
            'section_id' => null,
            'color_mode' => 'sector',
            'custom_color' => null,
            'overlay_opacity' => 20,
            'section_colors' => [],
            'orientation' => 'portrait',
        ]);

        $this->assertSame(5, $data['pages']->flatten(1)->count());
        $this->assertSame(2, $data['pageCount']);
        $this->assertSame(2, $data['cols']);
        $this->assertSame(2, $data['rows']);
        $this->assertTrue($data['pages']->flatten(1)->contains(fn ($l) => $l['label'] === 'A1' && $l['occupied'] === true));
        $this->assertTrue($data['pages']->flatten(1)->contains(fn ($l) => $l['label'] === 'A2' && $l['occupied'] === false));
        $this->assertSame($section->layout_color, $data['pages']->first()->first()['overlay_color']);
        $this->assertSame(SeatLabelLayout::rgba($section->layout_color, 20), $data['pages']->first()->first()['overlay_rgba']);
    }

    public function test_custom_color_and_opacity_apply_to_all_labels(): void
    {
        [$event] = $this->createEventWithSeats(2);

        $data = app(SeatLabelPdfService::class)->buildPdfData($event, [
            'per_page' => 2,
            'section_id' => null,
            'color_mode' => 'custom',
            'custom_color' => '#059669',
            'overlay_opacity' => 35,
            'section_colors' => [],
            'orientation' => 'portrait',
        ]);

        foreach ($data['pages']->flatten(1) as $label) {
            $this->assertSame('#059669', $label['overlay_color']);
            $this->assertSame(SeatLabelLayout::rgba('#059669', 35), $label['overlay_rgba']);
        }
    }

    public function test_general_admission_section_generates_capacity_labels(): void
    {
        $venue = Venue::create([
            'name' => 'Sala GA',
            'address' => 'Calle 1',
            'seat_rows' => 1,
            'seat_columns' => 1,
        ]);
        $section = Section::create([
            'venue_id' => $venue->id,
            'name' => 'General',
            'slug' => 'general',
            'sort_order' => 1,
            'has_seats' => false,
            'capacity' => 4,
            'layout_color' => '#0891B2',
        ]);
        $event = Event::create([
            'name' => 'Evento GA',
            'starts_at' => now()->addWeek(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'GA',
            'is_active' => true,
        ]);

        $labels = app(SeatLabelPdfService::class)->labelsForEvent($event);

        $this->assertCount(4, $labels);
        $this->assertSame('General 1', $labels->first()['label']);
        $this->assertSame($section->id, $labels->first()['section_id']);
    }

    public function test_labels_follow_row_then_number_as_a1_a2_b1(): void
    {
        $venue = Venue::create([
            'name' => 'Sala filas',
            'address' => 'Calle 1',
            'seat_rows' => 2,
            'seat_columns' => 3,
        ]);
        $section = Section::create([
            'venue_id' => $venue->id,
            'name' => 'Platea',
            'slug' => 'platea-filas',
            'sort_order' => 1,
            'has_seats' => true,
            'layout_color' => '#2563EB',
        ]);
        $event = Event::create([
            'name' => 'Evento filas',
            'starts_at' => now()->addWeek(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'FIL',
            'is_active' => true,
        ]);

        Seat::create(['venue_id' => $venue->id, 'section_id' => $section->id, 'row' => 2, 'number' => 2, 'label' => 'B2', 'blocked' => false]);
        Seat::create(['venue_id' => $venue->id, 'section_id' => $section->id, 'row' => 1, 'number' => 3, 'label' => 'A3', 'blocked' => false]);
        Seat::create(['venue_id' => $venue->id, 'section_id' => $section->id, 'row' => 1, 'number' => 1, 'label' => 'A1', 'blocked' => false]);
        Seat::create(['venue_id' => $venue->id, 'section_id' => $section->id, 'row' => 2, 'number' => 1, 'label' => 'B1', 'blocked' => false]);
        Seat::create(['venue_id' => $venue->id, 'section_id' => $section->id, 'row' => 1, 'number' => 2, 'label' => 'A2', 'blocked' => false]);

        $labels = app(SeatLabelPdfService::class)->labelsForEvent($event);

        $this->assertSame(['A1', 'A2', 'A3', 'B1', 'B2'], $labels->pluck('label')->all());
    }

    /**
     * @return array{0: Event, 1: \Illuminate\Support\Collection<int, Seat>, 2: Section}
     */
    private function createEventWithSeats(int $count): array
    {
        $venue = Venue::create([
            'name' => 'Teatro labels',
            'address' => 'Calle 1',
            'seat_rows' => 2,
            'seat_columns' => 8,
        ]);
        $section = Section::create([
            'venue_id' => $venue->id,
            'name' => 'Platea',
            'slug' => 'platea',
            'sort_order' => 1,
            'has_seats' => true,
            'layout_color' => '#2563EB',
        ]);
        $event = Event::create([
            'name' => 'Obra labels',
            'starts_at' => now()->addWeek(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'LBL',
            'is_active' => true,
        ]);

        $seats = collect();
        for ($i = 1; $i <= $count; $i++) {
            $seats->push(Seat::create([
                'venue_id' => $venue->id,
                'section_id' => $section->id,
                'row' => 1,
                'number' => $i,
                'label' => 'A-'.$i,
                'blocked' => false,
            ]));
        }

        return [$event, $seats, $section];
    }

    private function occupySeat(Event $event, Seat $seat): void
    {
        $buyer = User::factory()->create(['role' => User::ROLE_USER]);
        $reservation = Reservation::create([
            'user_id' => $buyer->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'LBL-OCC-001',
            'sale_amount' => 50,
            'confirmed_payment_at' => now(),
            'expires_at' => null,
        ]);
        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seat->id,
            'holder_name' => 'Ocupante',
            'position' => 1,
        ]);
    }
}
