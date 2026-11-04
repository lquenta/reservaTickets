<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\Seat;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueLayoutElement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSeatBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_block_and_unblock_seat_for_event(): void
    {
        [$event, $seat] = $this->createEventWithSeat();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.events.seats.block', [$event, $seat]))
            ->assertRedirect(route('admin.events.seats', $event));

        $this->assertDatabaseHas('event_seat_blocks', [
            'event_id' => $event->id,
            'seat_id' => $seat->id,
            'expires_at' => null,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.events.seats.unblock', [$event, $seat]))
            ->assertRedirect(route('admin.events.seats', $event));

        $this->assertDatabaseMissing('event_seat_blocks', [
            'event_id' => $event->id,
            'seat_id' => $seat->id,
        ]);
    }

    public function test_admin_cannot_block_occupied_seat(): void
    {
        [$event, $seat] = $this->createEventWithSeat();
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'user']);

        $reservation = Reservation::create([
            'user_id' => $buyer->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'TEST-'.strtoupper(\Illuminate\Support\Str::random(8)),
            'expires_at' => null,
        ]);

        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seat->id,
            'holder_name' => 'Comprador',
            'position' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.events.seats.block', [$event, $seat]))
            ->assertRedirect(route('admin.events.seats', $event));

        $this->assertDatabaseMissing('event_seat_blocks', [
            'event_id' => $event->id,
            'seat_id' => $seat->id,
        ]);
    }

    public function test_blocked_seat_is_not_available_in_seats_endpoint_for_client(): void
    {
        [$event, $seat] = $this->createEventWithSeat();
        $client = User::factory()->create(['role' => 'user']);
        $event->blockedSeats()->syncWithoutDetaching([$seat->id]);

        $response = $this->actingAs($client)
            ->getJson(route('reservations.seats', $event))
            ->assertOk();

        $returnedSeat = collect($response->json('seats'))->firstWhere('id', $seat->id);

        $this->assertNotNull($returnedSeat);
        $this->assertTrue($returnedSeat['blocked']);
        $this->assertFalse($returnedSeat['available']);
    }

    public function test_admin_seats_layout_map_includes_available_and_occupied_seat_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $venue = Venue::create([
            'name' => 'Venue layout',
            'address' => 'Calle 123',
            'seat_rows' => 5,
            'seat_columns' => 5,
        ]);

        $availableSeat = Seat::create([
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 1,
            'label' => 'A-1',
            'blocked' => false,
        ]);
        $occupiedSeat = Seat::create([
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 2,
            'label' => 'A-2',
            'blocked' => false,
        ]);

        foreach ([$availableSeat, $occupiedSeat] as $seat) {
            VenueLayoutElement::create([
                'venue_id' => $venue->id,
                'seat_id' => $seat->id,
                'type' => VenueLayoutElement::TYPE_SEAT,
                'x' => 10 + ($seat->number * 50),
                'y' => 20,
                'w' => 48,
                'h' => 48,
                'rotation' => 0,
                'z_index' => $seat->number,
                'meta' => [],
            ]);
        }

        $event = Event::create([
            'name' => 'Evento layout',
            'description' => 'Test',
            'starts_at' => now()->addDay(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'EVT',
            'is_active' => true,
        ]);

        $buyer = User::factory()->create(['role' => 'user']);
        $reservation = Reservation::create([
            'user_id' => $buyer->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'TEST-'.strtoupper(\Illuminate\Support\Str::random(8)),
            'expires_at' => null,
        ]);
        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $occupiedSeat->id,
            'holder_name' => 'Comprador',
            'position' => 1,
        ]);

        $blockPath = str_replace('/', '\\/', parse_url(route('admin.events.seats.block', [$event, $availableSeat]), PHP_URL_PATH));
        $unblockPath = str_replace('/', '\\/', parse_url(route('admin.events.seats.unblock', [$event, $availableSeat]), PHP_URL_PATH));

        $this->actingAs($admin)
            ->get(route('admin.events.seats', $event))
            ->assertOk()
            ->assertSee('href="'.route('admin.events.index').'"', false)
            ->assertSee('Plano del venue', false)
            ->assertSee('&quot;readonly&quot;:false', false)
            ->assertSee('Bloqueada solo en este evento', false)
            ->assertSee('Clic en una butaca disponible para bloquearla', false)
            ->assertSee('Bloqueo temporal', false)
            ->assertSee('máximo 24', false)
            ->assertSee($blockPath, false)
            ->assertSee($unblockPath, false)
            ->assertSee('&quot;occupied&quot;:false', false)
            ->assertSee('&quot;occupied&quot;:true', false);
    }

    public function test_admin_can_block_seat_for_up_to_24_hours(): void
    {
        $this->travelTo(now()->startOfMinute());

        [$event, $seat] = $this->createEventWithSeat();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.events.seats.block', [$event, $seat]), ['hours' => 24])
            ->assertRedirect(route('admin.events.seats', $event))
            ->assertSessionHas('message');

        $pivot = $event->blockedSeats()->first()->pivot;
        $this->assertNotNull($pivot->expires_at);
        $this->assertSame(now()->addHours(24)->format('Y-m-d H:i:s'), $pivot->expires_at->format('Y-m-d H:i:s'));
        $this->assertTrue($event->fresh()->blockedSeatIds()->contains($seat->id));
    }

    public function test_admin_cannot_block_seat_for_more_than_24_hours(): void
    {
        [$event, $seat] = $this->createEventWithSeat();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.events.seats', $event))
            ->post(route('admin.events.seats.block', [$event, $seat]), ['hours' => 25])
            ->assertRedirect(route('admin.events.seats', $event))
            ->assertSessionHasErrors('hours');

        $this->assertDatabaseMissing('event_seat_blocks', [
            'event_id' => $event->id,
            'seat_id' => $seat->id,
        ]);
    }

    public function test_expired_temporary_block_releases_the_seat(): void
    {
        [$event, $seat] = $this->createEventWithSeat();
        $client = User::factory()->create(['role' => 'user']);
        $event->blockedSeats()->syncWithoutDetaching([
            $seat->id => ['expires_at' => now()->subMinute()],
        ]);

        $this->assertFalse($event->fresh()->blockedSeatIds()->contains($seat->id));
        $this->assertTrue($event->availableSeats()->whereKey($seat->id)->exists());

        $response = $this->actingAs($client)
            ->getJson(route('reservations.seats', $event))
            ->assertOk();

        $returnedSeat = collect($response->json('seats'))->firstWhere('id', $seat->id);
        $this->assertFalse($returnedSeat['blocked']);
        $this->assertTrue($returnedSeat['available']);
    }

    public function test_active_temporary_block_hides_seat_from_clients_and_shows_expiry_on_admin_map(): void
    {
        [$event, $seat] = $this->createEventWithSeat();
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'user']);
        $event->blockedSeats()->syncWithoutDetaching([
            $seat->id => ['expires_at' => now()->addHours(5)],
        ]);

        $clientResponse = $this->actingAs($client)
            ->getJson(route('reservations.seats', $event))
            ->assertOk();
        $returnedSeat = collect($clientResponse->json('seats'))->firstWhere('id', $seat->id);
        $this->assertTrue($returnedSeat['blocked']);
        $this->assertFalse($returnedSeat['available']);

        $this->actingAs($admin)
            ->get(route('admin.events.seats', $event))
            ->assertOk()
            ->assertSee('bloqueada hasta', false);
    }

    public function test_client_cannot_reserve_event_blocked_seat_even_if_payload_is_manipulated(): void
    {
        [$event, $seat] = $this->createEventWithSeat();
        $client = User::factory()->create(['role' => 'user']);
        $event->blockedSeats()->syncWithoutDetaching([$seat->id]);

        $this->actingAs($client)
            ->from(route('reservations.create', $event))
            ->post(route('reservations.store'), [
                'event_id' => $event->id,
                'single_name' => 1,
                'holder_name' => 'Cliente Final',
                'seat_ids' => [$seat->id],
            ])
            ->assertRedirect(route('reservations.create', $event))
            ->assertSessionHasErrors('seat_ids');

        $this->assertDatabaseMissing('reservations', [
            'user_id' => $client->id,
            'event_id' => $event->id,
        ]);
    }

    private function createEventWithSeat(): array
    {
        $venue = Venue::create([
            'name' => 'Venue test',
            'address' => 'Calle 123',
            'seat_rows' => 5,
            'seat_columns' => 5,
        ]);

        $event = Event::create([
            'name' => 'Evento test',
            'description' => 'Evento para pruebas',
            'starts_at' => now()->addDay(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'EVT',
            'is_active' => true,
        ]);

        $seat = Seat::create([
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 1,
            'label' => 'A-1',
            'blocked' => false,
        ]);

        return [$event, $seat];
    }
}
