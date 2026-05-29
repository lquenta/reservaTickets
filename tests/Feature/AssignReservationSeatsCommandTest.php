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

class AssignReservationSeatsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_updates_holder_name_and_seat(): void
    {
        [$event, $seatA, $seatB, $reservation, $ticket] = $this->createReservation();

        $this->artisan('tickets:assign', [
            'payment_code' => $reservation->payment_code,
            'pairs' => ["{$ticket->id}:B2:Nuevo Nombre"],
        ])->assertSuccessful();

        $ticket->refresh();
        $this->assertSame('Nuevo Nombre', $ticket->holder_name);
        $this->assertSame($seatB->id, $ticket->seat_id);
        $this->assertFalse($event->availableSeats()->where('id', $seatB->id)->exists());
        $this->assertTrue($event->availableSeats()->where('id', $seatA->id)->exists());
    }

    public function test_command_can_swap_seats_within_same_reservation(): void
    {
        [$event, $seatA, $seatB, $reservation, $ticketA] = $this->createReservation(withSecondTicket: true);
        $ticketB = $reservation->reservationTickets->firstWhere('position', 2);

        $this->artisan('tickets:assign', [
            'payment_code' => $reservation->payment_code,
            'pairs' => [
                "{$ticketA->id}:B2:",
                "{$ticketB->id}:A1:",
            ],
        ])->assertSuccessful();

        $this->assertSame($seatB->id, $ticketA->fresh()->seat_id);
        $this->assertSame($seatA->id, $ticketB->fresh()->seat_id);
    }

    /**
     * @return array{0: Event, 1: Seat, 2: Seat, 3: Reservation, 4: ReservationTicket}
     */
    private function createReservation(bool $withSecondTicket = false): array
    {
        $venue = Venue::create([
            'name' => 'Venue assign',
            'address' => 'Calle 1',
            'seat_rows' => 2,
            'seat_columns' => 2,
        ]);

        $seatA = Seat::create(['venue_id' => $venue->id, 'row' => 1, 'number' => 1, 'label' => 'A-1', 'blocked' => false]);
        $seatB = Seat::create(['venue_id' => $venue->id, 'row' => 2, 'number' => 2, 'label' => 'B-2', 'blocked' => false]);

        $event = Event::create([
            'name' => 'Evento assign',
            'description' => 'Test',
            'starts_at' => now()->addWeek(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'ASG',
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
            'payment_code' => 'ASG-TEST-01',
            'sale_amount' => 0,
            'confirmed_payment_at' => now(),
        ]);

        $ticketA = ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seatA->id,
            'holder_name' => 'Invitado A',
            'position' => 1,
        ]);

        if ($withSecondTicket) {
            ReservationTicket::create([
                'reservation_id' => $reservation->id,
                'seat_id' => $seatB->id,
                'holder_name' => 'Invitado B',
                'position' => 2,
            ]);
        }

        return [$event, $seatA, $seatB, $reservation, $ticketA];
    }
}
