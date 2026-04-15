<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\Seat;
use App\Models\User;
use App\Models\Venue;
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
            'payment_code' => 'TEST-' . strtoupper(\Illuminate\Support\Str::random(8)),
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
