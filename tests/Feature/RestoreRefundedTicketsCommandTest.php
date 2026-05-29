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

class RestoreRefundedTicketsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_lists_and_restores_refunded_tickets(): void
    {
        [$event, $seatA, $seatB, $reservation, $ticketA, $ticketB] = $this->createHonoredGuestReservation();
        $ticketA->update(['refunded_at' => now()]);
        $ticketB->update(['refunded_at' => now()]);
        $reservation->update(['status' => Reservation::STATUS_REEMBOLSADO, 'refunded_at' => now()]);

        $this->artisan('refunds:restore', ['payment_code' => $reservation->payment_code, '--list' => true])
            ->assertSuccessful();

        $this->artisan('refunds:restore', [
            'payment_code' => $reservation->payment_code,
            'ticket_ids' => [(string) $ticketA->id, (string) $ticketB->id],
        ])->assertSuccessful();

        $reservation->refresh();
        $this->assertSame(Reservation::STATUS_CONFIRMADO, $reservation->status);
        $this->assertNull($ticketA->fresh()->refunded_at);
        $this->assertFalse($event->availableSeats()->where('id', $seatA->id)->exists());
    }

    /**
     * @return array{0: Event, 1: Seat, 2: Seat, 3: Reservation, 4: ReservationTicket, 5: ReservationTicket}
     */
    private function createHonoredGuestReservation(): array
    {
        $venue = Venue::create([
            'name' => 'Venue restore',
            'address' => 'Calle 1',
            'seat_rows' => 2,
            'seat_columns' => 2,
        ]);

        $seatA = Seat::create(['venue_id' => $venue->id, 'row' => 1, 'number' => 1, 'label' => 'A-1', 'blocked' => false]);
        $seatB = Seat::create(['venue_id' => $venue->id, 'row' => 1, 'number' => 2, 'label' => 'A-2', 'blocked' => false]);

        $event = Event::create([
            'name' => 'Evento restore',
            'description' => 'Test',
            'starts_at' => now()->addWeek(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'RST',
            'is_active' => true,
        ]);

        TicketTemplate::create([
            'event_id' => $event->id,
            'price' => 50,
            'design' => TicketTemplate::defaultDesign(),
        ]);

        $reservation = Reservation::create([
            'user_id' => User::factory()->create()->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'sale_type' => Reservation::SALE_TYPE_HONORED_GUEST,
            'payment_code' => 'RST-HONOR-01',
            'sale_amount' => 0,
            'confirmed_payment_at' => now(),
        ]);

        $ticketA = ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seatA->id,
            'holder_name' => 'Invitado A',
            'position' => 1,
        ]);

        $ticketB = ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seatB->id,
            'holder_name' => 'Invitado B',
            'position' => 2,
        ]);

        return [$event, $seatA, $seatB, $reservation, $ticketA, $ticketB];
    }
}
