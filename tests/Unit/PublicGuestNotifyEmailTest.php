<?php

namespace Tests\Unit;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicGuestNotifyEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_email_strips_public_guest_unique_tag(): void
    {
        $user = User::factory()->create([
            'is_guest' => true,
            'provisioned_via' => User::PROVISIONED_VIA_PUBLIC_GUEST,
            'email' => 'ana+pub-01hxyzabc@example.com',
        ]);

        $this->assertSame('ana@example.com', $user->notifyEmail());
        $this->assertSame('ana@example.com', $user->displayEmail());
    }

    public function test_seller_guest_local_email_has_no_notify_address(): void
    {
        $user = User::factory()->create([
            'is_guest' => true,
            'email' => 'guest+abc@guest.local',
        ]);

        $this->assertNull($user->notifyEmail());
        $this->assertNull($user->displayEmail());
    }

    public function test_public_guest_reservation_delivers_to_typed_email_not_seller(): void
    {
        $guest = User::factory()->create([
            'is_guest' => true,
            'provisioned_via' => User::PROVISIONED_VIA_PUBLIC_GUEST,
            'email' => 'leo+pub-01hxyz@correo.com',
        ]);
        $reservation = new Reservation([
            'user_id' => $guest->id,
            'sale_type' => Reservation::SALE_TYPE_STANDARD,
        ]);
        $reservation->setRelation('user', $guest);
        $reservation->setRelation('soldBy', null);

        $this->assertSame('leo@correo.com', $reservation->ticketDeliveryEmail());
    }
}
