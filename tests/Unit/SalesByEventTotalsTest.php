<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\TicketTemplate;
use App\Models\User;
use App\Support\SalesByEventTotals;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesByEventTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_row_computes_average_unit_price(): void
    {
        $row = SalesByEventTotals::toRow([
            'event_id' => 7,
            'event_name' => 'Show',
            'tickets_sold' => 4,
            'total' => 320.0,
        ]);

        $this->assertSame(7, $row->event_id);
        $this->assertSame('Show', $row->event_name);
        $this->assertSame(4, $row->tickets_sold);
        $this->assertSame(80.0, $row->unit_price);
        $this->assertSame(320.0, $row->total);
    }

    public function test_to_row_zero_tickets_yields_zero_unit_price(): void
    {
        $row = SalesByEventTotals::toRow([
            'event_id' => 1,
            'event_name' => 'Empty',
            'tickets_sold' => 0,
            'total' => 0.0,
        ]);

        $this->assertSame(0.0, $row->unit_price);
        $this->assertSame(0.0, $row->total);
    }

    public function test_uses_sale_amount_including_presale_not_list_price(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00'));

        $user = User::factory()->create();
        $event = Event::create([
            'name' => 'Preventa Show',
            'starts_at' => now()->addMonth(),
            'venue' => 'Arena',
            'is_active' => true,
            'presale_enabled' => true,
            'presale_discount_type' => Event::PRESALE_TYPE_PERCENT,
            'presale_discount_value' => 20,
            'presale_starts_at' => now()->subDay(),
            'presale_ends_at' => now()->addDay(),
        ]);

        TicketTemplate::create([
            'event_id' => $event->id,
            'price' => 100,
        ]);

        // List price would be 200; cobrado con preventa 20% = 160
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'PRE-001',
            'sale_amount' => 160.00,
        ]);

        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'holder_name' => 'A',
            'position' => 1,
        ]);
        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'holder_name' => 'B',
            'position' => 2,
        ]);

        $rows = SalesByEventTotals::fromReservations(
            Reservation::with(['event', 'reservationTickets'])->whereKey($reservation->id)->get()
        );

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows->first()->tickets_sold);
        $this->assertSame(160.0, $rows->first()->total);
        $this->assertSame(80.0, $rows->first()->unit_price);
    }

    public function test_excludes_honored_guest_reservations(): void
    {
        $user = User::factory()->create();
        $event = Event::create([
            'name' => 'VIP Night',
            'starts_at' => now()->addWeek(),
            'venue' => 'Hall',
            'is_active' => true,
        ]);

        $paid = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'PAY-001',
            'sale_type' => Reservation::SALE_TYPE_STANDARD,
            'sale_amount' => 50.00,
        ]);
        ReservationTicket::create([
            'reservation_id' => $paid->id,
            'holder_name' => 'Buyer',
            'position' => 1,
        ]);

        $guest = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'HON-001',
            'sale_type' => Reservation::SALE_TYPE_HONORED_GUEST,
            'sale_amount' => 0,
        ]);
        ReservationTicket::create([
            'reservation_id' => $guest->id,
            'holder_name' => 'Guest',
            'position' => 1,
        ]);

        $rows = SalesByEventTotals::fromReservations(
            Reservation::with(['event', 'reservationTickets'])->whereIn('id', [$paid->id, $guest->id])->get()
        );

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows->first()->tickets_sold);
        $this->assertSame(50.0, $rows->first()->total);
    }

    public function test_falls_back_to_pricing_with_presale_when_sale_amount_null(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00'));

        $user = User::factory()->create();
        $event = Event::create([
            'name' => 'Legacy Sale',
            'starts_at' => now()->addMonth(),
            'venue' => 'Arena',
            'is_active' => true,
            'presale_enabled' => true,
            'presale_discount_type' => Event::PRESALE_TYPE_FIXED,
            'presale_discount_value' => 25,
            'presale_starts_at' => now()->subDay(),
            'presale_ends_at' => now()->addDay(),
        ]);

        TicketTemplate::create([
            'event_id' => $event->id,
            'price' => 100,
        ]);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'LEG-001',
            'sale_amount' => null,
        ]);
        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'holder_name' => 'Legacy',
            'position' => 1,
        ]);

        $rows = SalesByEventTotals::fromReservations(
            Reservation::with(['event.ticketTemplate', 'reservationTickets'])->whereKey($reservation->id)->get()
        );

        // 100 - 25 Bs preventa
        $this->assertSame(75.0, $rows->first()->total);
        $this->assertSame(75.0, $rows->first()->unit_price);
        $this->assertSame(1, $rows->first()->tickets_sold);
    }

    public function test_ignores_refunded_tickets_and_sums_multiple_reservations(): void
    {
        $user = User::factory()->create();
        $eventA = Event::create([
            'name' => 'Event A',
            'starts_at' => now()->addWeek(),
            'venue' => 'A',
            'is_active' => true,
        ]);
        $eventB = Event::create([
            'name' => 'Event B',
            'starts_at' => now()->addWeeks(2),
            'venue' => 'B',
            'is_active' => true,
        ]);

        $r1 = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $eventA->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'A-001',
            'sale_amount' => 90.00, // remaining after partial refund
        ]);
        ReservationTicket::create([
            'reservation_id' => $r1->id,
            'holder_name' => 'Keep',
            'position' => 1,
        ]);
        ReservationTicket::create([
            'reservation_id' => $r1->id,
            'holder_name' => 'Refunded',
            'position' => 2,
            'refunded_at' => now(),
        ]);

        $r2 = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $eventA->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'A-002',
            'sale_amount' => 100.00,
        ]);
        ReservationTicket::create([
            'reservation_id' => $r2->id,
            'holder_name' => 'Other',
            'position' => 1,
        ]);

        $r3 = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $eventB->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'B-001',
            'sale_amount' => 40.00,
        ]);
        ReservationTicket::create([
            'reservation_id' => $r3->id,
            'holder_name' => 'B1',
            'position' => 1,
        ]);

        $rows = SalesByEventTotals::fromReservations(
            Reservation::with(['event', 'reservationTickets'])->whereIn('id', [$r1->id, $r2->id, $r3->id])->get()
        )->keyBy('event_id');

        $this->assertCount(2, $rows);

        $this->assertSame(2, $rows[$eventA->id]->tickets_sold); // refunded ticket excluded
        $this->assertSame(190.0, $rows[$eventA->id]->total);
        $this->assertSame(95.0, $rows[$eventA->id]->unit_price);

        $this->assertSame(1, $rows[$eventB->id]->tickets_sold);
        $this->assertSame(40.0, $rows[$eventB->id]->total);
    }
}
