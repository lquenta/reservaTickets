<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\Seat;
use App\Models\User;
use App\Models\Venue;
use App\Support\NombresPorEventoReportRows;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NombresPorEventoReportRowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rows_are_sorted_by_seat_row_then_number(): void
    {
        $venue = Venue::create([
            'name' => 'Venue sort',
            'address' => 'Calle 1',
            'seat_rows' => 3,
            'seat_columns' => 3,
        ]);

        $seatB1 = Seat::create(['venue_id' => $venue->id, 'row' => 2, 'number' => 1, 'label' => 'B1', 'blocked' => false]);
        $seatA2 = Seat::create(['venue_id' => $venue->id, 'row' => 1, 'number' => 2, 'label' => 'A2', 'blocked' => false]);

        $event = Event::create([
            'name' => 'Evento sort',
            'description' => 'Test',
            'starts_at' => now()->addWeek(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'SRT',
            'is_active' => true,
        ]);

        $buyer = User::factory()->create(['name' => 'Comprador Test']);
        $reservation = Reservation::create([
            'user_id' => $buyer->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'SRT-001',
            'sale_amount' => 100,
            'confirmed_payment_at' => now()->setDate(2026, 3, 15)->setTime(14, 30),
            'expires_at' => null,
        ]);

        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seatB1->id,
            'holder_name' => 'Titular B1',
            'position' => 1,
        ]);

        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seatA2->id,
            'holder_name' => 'Titular A2',
            'position' => 2,
        ]);

        $reservation->load(['reservationTickets.seat']);
        $rows = NombresPorEventoReportRows::fromReservations(collect([$reservation]));

        $this->assertSame(['A-2', 'B-1'], $rows->pluck('seat_label')->all());
        $this->assertSame('15/03/2026 14:30', $rows->first()->reserved_at->format('d/m/Y H:i'));
    }
}
