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

class NamesByEventReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_names_report_excludes_refunded_tickets_after_resale(): void
    {
        [$event, $seat, $originalReservation, $originalTicket] = $this->createConfirmedReservationWithOneSeat('Titular original');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.refunds.refund', $originalReservation), [
                'ticket_ids' => [$originalTicket->id],
            ])
            ->assertSessionHas('message');

        $buyer = User::factory()->create(['role' => 'user']);
        $newReservation = Reservation::create([
            'user_id' => $buyer->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'PRT-TEST-002',
            'sale_amount' => 50.00,
            'confirmed_payment_at' => now(),
            'expires_at' => null,
        ]);

        ReservationTicket::create([
            'reservation_id' => $newReservation->id,
            'seat_id' => $seat->id,
            'holder_name' => 'Titular nuevo',
            'position' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index', [
            'tab' => 'nombres-por-evento',
            'event_id' => $event->id,
        ]));

        $response->assertOk();
        $response->assertSee('Titular nuevo', false);
        $response->assertDontSee('Titular original', false);
        $response->assertSee($seat->display_label, false);
        $response->assertSee($buyer->name, false);
    }

    /**
     * @return array{0: Event, 1: Seat, 2: Reservation, 3: ReservationTicket}
     */
    private function createConfirmedReservationWithOneSeat(string $holderName): array
    {
        $venue = Venue::create([
            'name' => 'Venue names report',
            'address' => 'Calle 1',
            'seat_rows' => 3,
            'seat_columns' => 3,
        ]);

        $seat = Seat::create([
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 1,
            'label' => 'B-2',
            'blocked' => false,
        ]);

        $event = Event::create([
            'name' => 'Evento nombres',
            'description' => 'Test',
            'starts_at' => now()->addWeek(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'NOM',
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
            'payment_code' => 'NOM-TEST-001',
            'sale_amount' => 50.00,
            'confirmed_payment_at' => now(),
            'expires_at' => null,
        ]);

        $ticket = ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seat->id,
            'holder_name' => $holderName,
            'position' => 1,
        ]);

        return [$event, $seat, $reservation, $ticket];
    }
}
