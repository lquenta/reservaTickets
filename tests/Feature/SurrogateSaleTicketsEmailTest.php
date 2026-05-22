<?php

namespace Tests\Feature;

use App\Jobs\NotifyAdminNewReservationJob;
use App\Jobs\SendReservationTicketsJob;
use App\Mail\TicketsSentMail;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SurrogateSaleTicketsEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_surrogate_checkout_confirm_does_not_dispatch_ticket_email_job(): void
    {
        Bus::fake();
        Storage::fake('public');

        $seller = User::factory()->create(['role' => User::ROLE_VENDEDOR]);
        $client = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);
        $event = $this->createEvent();
        $reservation = $this->createSurrogateReservation($seller, $client, $event);

        $this->actingAs($seller)
            ->post(route('seller.surrogate-sale.checkout.confirm', $reservation), [
                'accept_terms' => '1',
                'payment_receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertRedirect(route('seller.events.index'))
            ->assertSessionHas('message', 'Venta registrada. Pendiente de autorización.');

        $this->assertSame(Reservation::STATUS_PENDIENTE_PAGO, $reservation->fresh()->status);
        Bus::assertDispatched(NotifyAdminNewReservationJob::class);
        Bus::assertNotDispatched(SendReservationTicketsJob::class);
    }

    public function test_admin_surrogate_checkout_confirm_does_not_dispatch_ticket_email_job(): void
    {
        Bus::fake();
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->unverified()->create(['role' => 'user']);
        $event = $this->createEvent();
        $reservation = $this->createSurrogateReservation($admin, $client, $event);

        $this->actingAs($admin)
            ->post(route('admin.surrogate-sale.checkout.confirm', $reservation), [
                'accept_terms' => '1',
                'seller_delivery_responsibility' => '1',
                'payment_receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertRedirect(route('admin.reservations.index'))
            ->assertSessionHas('message', 'Venta registrada. Pendiente de autorización.');

        Bus::assertDispatched(NotifyAdminNewReservationJob::class);
        Bus::assertNotDispatched(SendReservationTicketsJob::class);
    }

    public function test_client_checkout_confirm_does_not_dispatch_ticket_email_job(): void
    {
        Bus::fake();
        Storage::fake('public');

        $client = User::factory()->create(['role' => 'user']);
        $event = $this->createEvent();
        $reservation = Reservation::create([
            'user_id' => $client->id,
            'sale_type' => Reservation::SALE_TYPE_STANDARD,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_INICIADO,
            'payment_code' => 'STD-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($client)
            ->post(route('checkout.confirm', $reservation), [
                'accept_terms' => '1',
                'payment_receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertRedirect(route('reservations.index'))
            ->assertSessionHas('message', 'Reserva registrada. Recibirás los tickets por correo una vez se autorice el pago.');

        Bus::assertDispatched(NotifyAdminNewReservationJob::class);
        Bus::assertNotDispatched(SendReservationTicketsJob::class);
    }

    public function test_admin_resend_tickets_dispatches_job_with_force(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'user', 'email' => 'client@example.com']);
        $event = $this->createEvent();
        $reservation = Reservation::create([
            'user_id' => $client->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'TST-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reservations.resend-tickets', $reservation))
            ->assertRedirect(route('admin.reservations.index'));

        Bus::assertDispatched(
            SendReservationTicketsJob::class,
            fn (SendReservationTicketsJob $job) => $job->force && ! $job->fromAuthorize
        );
    }

    public function test_send_reservation_tickets_job_source_never_checks_email_verification(): void
    {
        $source = file_get_contents(app_path('Jobs/SendReservationTicketsJob.php'));

        $this->assertIsString($source);
        $this->assertStringNotContainsString('hasVerifiedEmail', $source);
        $this->assertStringNotContainsString('email_verified_at', $source);
        $this->assertStringNotContainsString('allowUnverifiedRecipient', $source);
    }

    public function test_admin_authorize_dispatches_ticket_email_job_for_unverified_client(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => User::ROLE_VENDEDOR]);
        $client = User::factory()->unverified()->create(['role' => 'user']);
        $event = $this->createEvent();
        $reservation = $this->createSurrogateReservation($seller, $client, $event, Reservation::STATUS_PENDIENTE_PAGO);

        $this->actingAs($admin)
            ->post(route('admin.reservations.authorize', $reservation))
            ->assertRedirect(route('admin.reservations.index'));

        $this->assertSame(Reservation::STATUS_CONFIRMADO, $reservation->fresh()->status);
        Bus::assertDispatched(SendReservationTicketsJob::class, fn (SendReservationTicketsJob $job) => $job->fromAuthorize);
    }

    public function test_admin_authorize_sends_ticket_email_even_if_tickets_emailed_at_set(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => User::ROLE_VENDEDOR]);
        $client = User::factory()->unverified()->create(['role' => 'user', 'email' => 'client@example.com']);
        $event = $this->createEvent();
        $reservation = $this->createSurrogateReservation($seller, $client, $event, Reservation::STATUS_PENDIENTE_PAGO);
        $reservation->update(['tickets_emailed_at' => now()->subHour()]);

        $this->actingAs($admin)
            ->post(route('admin.reservations.authorize', $reservation))
            ->assertRedirect(route('admin.reservations.index'));

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true])->assertSuccessful();

        Mail::assertSent(TicketsSentMail::class, fn (TicketsSentMail $mail) => $mail->hasTo('client@example.com'));
        $this->assertNotNull($reservation->fresh()->tickets_emailed_at);
    }

    public function test_send_reservation_tickets_job_sends_mail_to_unverified_client(): void
    {
        Mail::fake();

        $client = User::factory()->unverified()->create(['role' => 'user', 'email' => 'unverified@example.com']);
        $event = $this->createEvent();
        $reservation = Reservation::create([
            'user_id' => $client->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'TST-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
        ]);

        $job = new SendReservationTicketsJob($reservation);
        $job->handle();

        Mail::assertSent(TicketsSentMail::class, function (TicketsSentMail $mail) use ($client) {
            return $mail->hasTo($client->email);
        });
        $this->assertNotNull($reservation->fresh()->tickets_emailed_at);
    }

    public function test_send_reservation_tickets_job_sends_mail_to_verified_client(): void
    {
        Mail::fake();

        $client = User::factory()->create(['role' => 'user', 'email' => 'verified@example.com']);
        $event = $this->createEvent();
        $reservation = Reservation::create([
            'user_id' => $client->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'TST-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
        ]);

        $job = new SendReservationTicketsJob($reservation);
        $job->handle();

        Mail::assertSent(TicketsSentMail::class, function (TicketsSentMail $mail) use ($client) {
            return $mail->hasTo($client->email);
        });
        $this->assertNotNull($reservation->fresh()->tickets_emailed_at);
    }

    public function test_admin_authorize_dispatches_ticket_email_job_for_verified_client(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => User::ROLE_VENDEDOR]);
        $client = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);
        $event = $this->createEvent();
        $reservation = $this->createSurrogateReservation($seller, $client, $event, Reservation::STATUS_PENDIENTE_PAGO);

        $this->actingAs($admin)
            ->post(route('admin.reservations.authorize', $reservation))
            ->assertRedirect(route('admin.reservations.index'));

        $this->assertSame(Reservation::STATUS_CONFIRMADO, $reservation->fresh()->status);
        Bus::assertDispatched(SendReservationTicketsJob::class, fn (SendReservationTicketsJob $job) => $job->fromAuthorize);
    }

    public function test_send_reservation_tickets_job_skips_when_already_emailed_without_force_or_authorize(): void
    {
        Mail::fake();

        $client = User::factory()->create(['role' => 'user', 'email' => 'client@example.com']);
        $event = $this->createEvent();
        $reservation = Reservation::create([
            'user_id' => $client->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'TST-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
            'tickets_emailed_at' => now()->subHour(),
        ]);

        (new SendReservationTicketsJob($reservation))->handle();

        Mail::assertNothingSent();
    }

    public function test_send_reservation_tickets_job_from_authorize_sends_when_already_emailed(): void
    {
        Mail::fake();

        $client = User::factory()->create(['role' => 'user', 'email' => 'client@example.com']);
        $event = $this->createEvent();
        $reservation = Reservation::create([
            'user_id' => $client->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'TST-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
            'tickets_emailed_at' => now()->subHour(),
        ]);

        (new SendReservationTicketsJob($reservation, fromAuthorize: true))->handle();

        Mail::assertSent(TicketsSentMail::class, fn (TicketsSentMail $mail) => $mail->hasTo('client@example.com'));
    }

    public function test_send_reservation_tickets_job_skips_non_confirmed_reservation(): void
    {
        $client = User::factory()->create(['role' => 'user']);
        $event = $this->createEvent();
        $reservation = Reservation::create([
            'user_id' => $client->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_PENDIENTE_PAGO,
            'payment_code' => 'TST-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
        ]);

        $job = new SendReservationTicketsJob($reservation);
        $job->handle();

        $this->assertNull($reservation->fresh()->tickets_emailed_at);
    }

    private function createEvent(): Event
    {
        return Event::create([
            'name' => 'Evento surrogado',
            'description' => 'Descripcion',
            'starts_at' => now()->addDay(),
            'venue' => 'Sala test',
            'payment_code_prefix' => 'SUR',
            'is_active' => true,
        ]);
    }

    private function createSurrogateReservation(
        User $seller,
        User $client,
        Event $event,
        string $status = Reservation::STATUS_INICIADO
    ): Reservation {
        return Reservation::create([
            'user_id' => $client->id,
            'sold_by_user_id' => $seller->id,
            'sale_type' => Reservation::SALE_TYPE_SURROGATE,
            'event_id' => $event->id,
            'status' => $status,
            'payment_code' => 'SUR-'.strtoupper(Str::random(8)),
            'expires_at' => now()->addMinutes(10),
        ]);
    }
}
