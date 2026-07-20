<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\Section;
use App\Models\TicketTemplate;
use App\Models\User;
use App\Models\Venue;
use App\Services\ReservationPricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationPricingPresaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sectioned_event_uses_per_section_presale_not_event_level(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00'));

        $user = User::factory()->create();
        $venue = Venue::create([
            'name' => 'Arena',
            'address' => 'Calle 1',
            'seat_rows' => 2,
            'seat_columns' => 2,
        ]);

        $event = Event::create([
            'name' => 'Show',
            'starts_at' => now()->addMonth(),
            'venue' => 'Arena',
            'venue_id' => $venue->id,
            'is_active' => true,
            'presale_enabled' => true,
            // Event-level would be 50% if wrongly used
            'presale_discount_type' => Event::PRESALE_TYPE_PERCENT,
            'presale_discount_value' => 50,
            'presale_starts_at' => now()->subDay(),
            'presale_ends_at' => now()->addDay(),
        ]);

        $vip = Section::create([
            'venue_id' => $venue->id,
            'name' => 'VIP',
            'slug' => 'vip-'.uniqid(),
            'has_seats' => false,
            'capacity' => 100,
            'sort_order' => 0,
        ]);
        $ga = Section::create([
            'venue_id' => $venue->id,
            'name' => 'GA',
            'slug' => 'ga-'.uniqid(),
            'has_seats' => false,
            'capacity' => 200,
            'sort_order' => 1,
        ]);

        $event->sections()->attach($vip->id, [
            'price' => 100,
            'sort_order' => 0,
            'presale_discount_type' => Event::PRESALE_TYPE_PERCENT,
            'presale_discount_value' => 20,
        ]);
        $event->sections()->attach($ga->id, [
            'price' => 80,
            'sort_order' => 1,
            'presale_discount_type' => Event::PRESALE_TYPE_FIXED,
            'presale_discount_value' => 10,
        ]);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_INICIADO,
            'sale_type' => Reservation::SALE_TYPE_STANDARD,
            'payment_code' => 'TEST-001',
            'expires_at' => now()->addHour(),
        ]);

        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'section_id' => $vip->id,
            'holder_name' => 'A',
            'position' => 1,
        ]);
        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'section_id' => $ga->id,
            'holder_name' => 'B',
            'position' => 2,
        ]);

        $reservation->refresh();
        $service = app(ReservationPricingService::class);

        // VIP 100-20%=80 + GA 80-10=70 => 150 (not 50% event-level)
        $this->assertSame(150.0, $service->totalForReservation($reservation));
        $this->assertSame(180.0, $service->listTotalForReservation($reservation));
    }

    public function test_template_price_gets_event_level_presale_when_no_sections(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00'));

        $user = User::factory()->create();
        $event = Event::create([
            'name' => 'Show GA',
            'starts_at' => now()->addMonth(),
            'venue' => 'Arena',
            'is_active' => true,
            'presale_enabled' => true,
            'presale_discount_type' => Event::PRESALE_TYPE_FIXED,
            'presale_discount_value' => 30,
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
            'status' => Reservation::STATUS_INICIADO,
            'sale_type' => Reservation::SALE_TYPE_STANDARD,
            'payment_code' => 'TEST-002',
            'expires_at' => now()->addHour(),
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

        $reservation->refresh();
        $service = app(ReservationPricingService::class);

        $this->assertSame(140.0, $service->totalForReservation($reservation));
        $this->assertSame(200.0, $service->listTotalForReservation($reservation));
    }

    public function test_honored_guest_remains_zero_with_presale(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00'));

        $user = User::factory()->create();
        $event = Event::create([
            'name' => 'Show',
            'starts_at' => now()->addMonth(),
            'venue' => 'Arena',
            'is_active' => true,
            'presale_enabled' => true,
            'presale_discount_type' => Event::PRESALE_TYPE_PERCENT,
            'presale_discount_value' => 50,
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
            'status' => Reservation::STATUS_INICIADO,
            'sale_type' => Reservation::SALE_TYPE_HONORED_GUEST,
            'payment_code' => 'TEST-003',
            'expires_at' => now()->addHour(),
        ]);

        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'holder_name' => 'VIP',
            'position' => 1,
        ]);

        $reservation->refresh();
        $this->assertSame(0.0, app(ReservationPricingService::class)->totalForReservation($reservation));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
