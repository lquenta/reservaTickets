<?php

namespace Tests\Feature;

use App\Jobs\SendReservationTicketsJob;
use App\Mail\TicketsSentMail;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicGuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_sees_login_register_or_guest_choice_instead_of_login(): void
    {
        $event = $this->createEvent();

        $this->get(route('events.index'))
            ->assertOk()
            ->assertDontSee('Inicia sesión para reservar')
            ->assertSee(route('reservations.entry', $event), false);

        $this->get(route('reservations.entry', $event))
            ->assertOk()
            ->assertSee('Iniciar sesión')
            ->assertSee('Registrarse')
            ->assertSee('Continuar como invitado')
            ->assertSee(route('reservations.create', $event), false);
    }

    public function test_logged_in_unverified_user_can_reserve_without_throwaway(): void
    {
        $event = $this->createEvent();
        $user = User::factory()->unverified()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('reservations.create', $event))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('reservations.store'), [
                'event_id' => $event->id,
                'quantity' => 1,
                'single_name' => 1,
                'holder_name' => 'Titular Uno',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);
        $this->assertFalse($user->fresh()->is_guest);
        $this->assertSame(0, User::where('is_guest', true)->count());
    }

    public function test_guest_checkout_requires_name_last_name_phone_and_email(): void
    {
        $event = $this->createEvent();

        $this->from(route('reservations.create', $event))
            ->post(route('reservations.store'), [
                'event_id' => $event->id,
                'quantity' => 1,
                'single_name' => 1,
                'holder_name' => 'Titular Uno',
            ])
            ->assertRedirect(route('reservations.create', $event))
            ->assertSessionHasErrors(['guest_first_name', 'guest_last_name', 'guest_phone', 'guest_email']);

        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_guest_creates_throwaway_user_and_tickets_go_to_typed_email(): void
    {
        Mail::fake();
        Storage::fake('public');

        $event = $this->createEvent();

        $response = $this->post(route('reservations.store'), [
            'event_id' => $event->id,
            'quantity' => 1,
            'single_name' => 1,
            'holder_name' => 'Titular Uno',
            'guest_first_name' => 'Ana',
            'guest_last_name' => 'Perez',
            'guest_phone' => '70001111',
            'guest_email' => 'ana@example.com',
            'guest_email_confirmation' => 'ana@example.com',
        ]);

        $reservation = Reservation::query()->first();
        $this->assertNotNull($reservation);
        $response->assertRedirect(route('checkout.show', $reservation));

        $guest = $reservation->user;
        $this->assertTrue($guest->is_guest);
        $this->assertSame('Ana Perez', $guest->name);
        $this->assertSame('70001111', $guest->phone);
        $this->assertSame(User::PROVISIONED_VIA_PUBLIC_GUEST, $guest->provisioned_via);
        $this->assertSame('ana@example.com', $guest->notifyEmail());
        $this->assertNotSame('ana@example.com', $guest->email);
        $this->assertStringContainsString('+pub-', $guest->email);

        $this->get(route('checkout.show', $reservation))->assertOk();

        $this->post(route('checkout.confirm', $reservation), [
            'accept_terms' => '1',
            'payment_receipt' => UploadedFile::fake()->image('comprobante.jpg'),
        ])->assertRedirect(route('home'));

        $reservation->update(['status' => Reservation::STATUS_CONFIRMADO]);
        $this->assertSame('ana@example.com', $reservation->fresh()->ticketDeliveryEmail());

        (new SendReservationTicketsJob($reservation->fresh(), fromAuthorize: true))->handle();

        Mail::assertSent(TicketsSentMail::class, fn (TicketsSentMail $mail) => $mail->hasTo('ana@example.com'));
    }

    public function test_guest_cannot_use_admin_email(): void
    {
        $event = $this->createEvent();
        User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'admin@example.com']);

        $this->from(route('reservations.create', $event))
            ->post(route('reservations.store'), [
                'event_id' => $event->id,
                'quantity' => 1,
                'single_name' => 1,
                'holder_name' => 'Titular Uno',
                'guest_first_name' => 'Ana',
                'guest_last_name' => 'Perez',
                'guest_phone' => '70001111',
                'guest_email' => 'admin@example.com',
                'guest_email_confirmation' => 'admin@example.com',
            ])
            ->assertRedirect(route('reservations.create', $event))
            ->assertSessionHasErrors('guest_email');
    }

    public function test_register_redirects_to_events_not_verification(): void
    {
        $this->post(route('register'), [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@example.com',
            'email_confirmation' => 'nuevo@example.com',
            'ci' => '1234567',
            'phone' => '70000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('events.index'));
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
