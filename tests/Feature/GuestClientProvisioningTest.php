<?php

namespace Tests\Feature;

use App\Jobs\SendReservationTicketsJob;
use App\Mail\TicketsSentMail;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestClientProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_surrogate_start_defaults_to_guest_when_only_name_submitted(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = $this->createEvent();

        $this->actingAs($admin)
            ->post(route('admin.events.surrogate-sale.start', $event), [
                'client_name' => 'Invitado Por Defecto',
            ])
            ->assertRedirect(route('admin.events.surrogate-sale.seats', $event));

        $client = User::findOrFail(session('admin_surrogate.client_user_id'));
        $this->assertTrue($client->is_guest);
        $this->assertSame('Invitado Por Defecto', $client->name);
    }

    public function test_surrogate_start_with_deliver_tickets_creates_guest_user(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = $this->createEvent();

        $this->actingAs($admin)
            ->post(route('admin.events.surrogate-sale.start', $event), [
                'seller_will_deliver_tickets' => '1',
                'client_name' => 'Invitado Temporal',
            ])
            ->assertRedirect(route('admin.events.surrogate-sale.seats', $event));

        $clientId = session('admin_surrogate.client_user_id');
        $this->assertNotNull($clientId);

        $client = User::findOrFail($clientId);
        $this->assertTrue($client->is_guest);
        $this->assertSame('Invitado Temporal', $client->name);
        $this->assertNull($client->phone);
        $this->assertStringEndsWith('@guest.local', $client->email);
        $this->assertSame(User::PROVISIONED_VIA_SURROGATE, $client->provisioned_via);

        Notification::assertNothingSent();
    }

    public function test_honored_guest_start_with_deliver_tickets_creates_guest_user(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = $this->createEvent();

        $this->actingAs($admin)
            ->post(route('admin.events.honored-guest.start', $event), [
                'seller_will_deliver_tickets' => '1',
                'client_name' => 'Invitado Honor',
            ])
            ->assertRedirect(route('admin.events.honored-guest.seats', $event));

        $client = User::findOrFail(session('admin_honored.client_user_id'));
        $this->assertTrue($client->is_guest);
        $this->assertSame(User::PROVISIONED_VIA_HONORED_GUEST, $client->provisioned_via);

        Notification::assertNothingSent();
    }

    public function test_guest_surrogate_checkout_auto_acknowledges_delivery_without_checkbox(): void
    {
        Storage::fake('public');

        $seller = User::factory()->create(['role' => User::ROLE_VENDEDOR]);
        $guest = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_guest' => true,
            'email' => 'guest+'.strtolower((string) Str::ulid()).'@guest.local',
            'phone' => null,
            'email_verified_at' => null,
        ]);
        $event = $this->createEvent();
        $reservation = Reservation::create([
            'user_id' => $guest->id,
            'sold_by_user_id' => $seller->id,
            'sale_type' => Reservation::SALE_TYPE_SURROGATE,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_INICIADO,
            'payment_code' => 'SUR-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($seller)
            ->post(route('seller.surrogate-sale.checkout.confirm', $reservation), [
                'accept_terms' => '1',
                'payment_receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertRedirect(route('seller.events.index'));

        $fresh = $reservation->fresh();
        $this->assertNotNull($fresh->seller_delivery_acknowledged_at);
        $this->assertSame($seller->id, $fresh->seller_delivery_acknowledged_by_user_id);
    }

    public function test_authorize_sends_ticket_email_to_sold_by_for_guest_client(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'admin@example.com']);
        $seller = User::factory()->create(['role' => User::ROLE_VENDEDOR, 'email' => 'seller@example.com']);
        $guest = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_guest' => true,
            'email' => 'guest+test@guest.local',
            'phone' => null,
        ]);
        $event = $this->createEvent();
        $reservation = Reservation::create([
            'user_id' => $guest->id,
            'sold_by_user_id' => $seller->id,
            'sale_type' => Reservation::SALE_TYPE_SURROGATE,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_PENDIENTE_PAGO,
            'payment_code' => 'SUR-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reservations.authorize', $reservation))
            ->assertRedirect(route('admin.reservations.index'));

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true])->assertSuccessful();

        Mail::assertSent(TicketsSentMail::class, fn (TicketsSentMail $mail) => $mail->hasTo('seller@example.com'));
        Mail::assertNotSent(TicketsSentMail::class, fn (TicketsSentMail $mail) => $mail->hasTo('guest+test@guest.local'));
    }

    public function test_send_reservation_tickets_job_uses_ticket_delivery_email(): void
    {
        Mail::fake();

        $seller = User::factory()->create(['role' => User::ROLE_VENDEDOR, 'email' => 'seller@example.com']);
        $guest = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_guest' => true,
            'email' => 'guest+abc@guest.local',
        ]);
        $event = $this->createEvent();
        $reservation = Reservation::create([
            'user_id' => $guest->id,
            'sold_by_user_id' => $seller->id,
            'sale_type' => Reservation::SALE_TYPE_HONORED_GUEST,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'HON-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->assertSame('seller@example.com', $reservation->ticketDeliveryEmail());

        (new SendReservationTicketsJob($reservation, fromAuthorize: true))->handle();

        Mail::assertSent(TicketsSentMail::class, fn (TicketsSentMail $mail) => $mail->hasTo('seller@example.com'));
    }

    public function test_normal_surrogate_start_still_requires_email_and_sends_verification(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = $this->createEvent();

        $this->actingAs($admin)
            ->post(route('admin.events.surrogate-sale.start', $event), [
                'seller_will_deliver_tickets' => '0',
                'client_name' => 'Cliente Normal',
                'client_email' => 'nuevo@example.com',
                'client_email_confirmation' => 'nuevo@example.com',
                'client_phone' => '70000000',
            ])
            ->assertRedirect(route('admin.events.surrogate-sale.seats', $event));

        $client = User::where('email', 'nuevo@example.com')->first();
        $this->assertNotNull($client);
        $this->assertFalse($client->is_guest);

        Notification::assertSentTo($client, VerifyEmailNotification::class);
    }

    private function createEvent(): Event
    {
        return Event::create([
            'name' => 'Evento test',
            'description' => 'Descripcion',
            'starts_at' => now()->addDay(),
            'venue' => 'Sala test',
            'payment_code_prefix' => 'TST',
            'is_active' => true,
        ]);
    }
}
