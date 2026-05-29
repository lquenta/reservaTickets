<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\Seat;
use App\Models\TicketTemplate;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartialRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_refund_releases_only_selected_seats(): void
    {
        [$event, $seatA, $seatB, $reservation, $ticketA, $ticketB] = $this->createConfirmedReservationWithTwoSeats();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.refunds.refund', $reservation), [
                'ticket_ids' => [$ticketA->id],
                'redirect' => route('admin.refunds.index', ['event_id' => $event->id]),
            ])
            ->assertRedirect(route('admin.refunds.index', ['event_id' => $event->id]))
            ->assertSessionHas('message');

        $reservation->refresh();
        $ticketA->refresh();
        $ticketB->refresh();

        $this->assertSame(Reservation::STATUS_CONFIRMADO, $reservation->status);
        $this->assertNotNull($ticketA->refunded_at);
        $this->assertNull($ticketB->refunded_at);
        $this->assertTrue($event->availableSeats()->where('id', $seatA->id)->exists());
        $this->assertFalse($event->availableSeats()->where('id', $seatB->id)->exists());
    }

    public function test_full_refund_when_all_active_tickets_selected(): void
    {
        [$event, , , $reservation, $ticketA, $ticketB] = $this->createConfirmedReservationWithTwoSeats();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.refunds.refund', $reservation), [
                'ticket_ids' => [$ticketA->id, $ticketB->id],
            ])
            ->assertSessionHas('message');

        $reservation->refresh();
        $this->assertSame(Reservation::STATUS_REEMBOLSADO, $reservation->status);
        $this->assertNotNull($reservation->refunded_at);
    }

    public function test_cannot_refund_validated_ticket(): void
    {
        [$event, , , $reservation, $ticketA, $ticketB] = $this->createConfirmedReservationWithTwoSeats();
        $ticketA->update(['validated_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.refunds.index', ['event_id' => $event->id]))
            ->post(route('admin.refunds.refund', $reservation), [
                'ticket_ids' => [$ticketA->id],
            ])
            ->assertRedirect(route('admin.refunds.index', ['event_id' => $event->id]))
            ->assertSessionHas('error');

        $this->assertNull($ticketA->fresh()->refunded_at);
        $this->assertNull($ticketB->fresh()->refunded_at);
        $this->assertSame(Reservation::STATUS_CONFIRMADO, $reservation->fresh()->status);
    }

    public function test_refunded_ticket_cannot_be_validated_at_gate(): void
    {
        [$event, , , $reservation, $ticketA] = $this->createConfirmedReservationWithTwoSeats();
        $ticketA->update(['refunded_at' => now()]);
        config(['services.ticket_validator.api_key' => 'test-key']);

        $this->withHeader('X-API-Key', 'test-key')
            ->postJson('/api/v1/tickets/validate', [
                'code' => $reservation->payment_code.'-'.$ticketA->position,
            ])
            ->assertStatus(403)
            ->assertJson([
                'valid' => false,
                'message' => 'Entrada reembolsada',
            ]);
    }

    /**
     * @return array{0: Event, 1: Seat, 2: Seat, 3: Reservation, 4: ReservationTicket, 5: ReservationTicket}
     */
    private function createConfirmedReservationWithTwoSeats(): array
    {
        $venue = Venue::create([
            'name' => 'Venue partial refund',
            'address' => 'Calle 1',
            'seat_rows' => 3,
            'seat_columns' => 3,
        ]);

        $seatA = Seat::create([
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 1,
            'label' => 'A-1',
            'blocked' => false,
        ]);

        $seatB = Seat::create([
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 2,
            'label' => 'A-2',
            'blocked' => false,
        ]);

        $event = Event::create([
            'name' => 'Evento reembolso parcial',
            'description' => 'Test',
            'starts_at' => now()->addWeek(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'PRT',
            'is_active' => true,
        ]);

        TicketTemplate::create([
            'event_id' => $event->id,
            'price' => 50.00,
            'design' => TicketTemplate::defaultDesign(),
        ]);

        $buyer = User::factory()->create(['role' => 'user']);
        $reservation = Reservation::create([
            'user_id' => $buyer->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'PRT-TEST-001',
            'sale_amount' => 100.00,
            'confirmed_payment_at' => now(),
            'expires_at' => null,
        ]);

        $ticketA = ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seatA->id,
            'holder_name' => 'Persona A',
            'position' => 1,
        ]);

        $ticketB = ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seatB->id,
            'holder_name' => 'Persona B',
            'position' => 2,
        ]);

        return [$event, $seatA, $seatB, $reservation, $ticketA, $ticketB];
    }
}
