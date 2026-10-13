<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordLoginRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_password_recovery_and_keeps_captcha(): void
    {
        config([
            'services.recaptcha.site_key' => 'site-key',
            'services.recaptcha.secret_key' => 'secret-key',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('¿Olvidaste tu contraseña?', false)
            ->assertSee(route('password.request'), false)
            ->assertSee('g-recaptcha', false)
            ->assertSee('site-key', false);

        $user = User::factory()->create([
            'email' => 'ana@example.com',
            'password' => 'password123',
        ]);

        $this->post(route('login'), [
            'email' => 'ana@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('g-recaptcha-response');

        $this->assertGuest();
        $this->assertNotNull($user);
    }

    public function test_existing_email_on_register_points_to_password_recovery(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->from(route('register'))
            ->post(route('register'), [
                'name' => 'Ana Perez',
                'email' => 'ana@example.com',
                'ci' => '1234567',
                'phone' => '70000000',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->followingRedirects()
            ->from(route('register'))
            ->post(route('register'), [
                'name' => 'Ana Perez',
                'email' => 'ana@example.com',
                'ci' => '1234567',
                'phone' => '70000000',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSee('Recuperar contraseña', false);
    }

    public function test_register_accepts_ci_and_phone_with_separators(): void
    {
        $this->post(route('register'), [
            'name' => 'Ana Perez',
            'email' => 'Ana.Dots@Example.com',
            'ci' => '1.234.567',
            'phone' => '(591) 700-00000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('events.index'));

        $user = User::query()->where('email', 'ana.dots@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('1234567', $user->ci);
        $this->assertSame('(591)700-00000', $user->phone);
    }

    public function test_password_reset_link_sets_a_new_password(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'ana@example.com',
            'password' => 'clave-vieja',
        ]);

        $this->post(route('password.email'), ['email' => 'ana@example.com'])
            ->assertRedirect()
            ->assertSessionHas('message', 'Si el correo está registrado, te enviamos un enlace para elegir una contraseña nueva.');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'ana@example.com',
            'password' => 'clave-nueva-1',
            'password_confirmation' => 'clave-nueva-1',
        ])->assertRedirect(route('events.index'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => 'ana@example.com',
            'password' => 'clave-nueva-1',
        ])->assertRedirect(route('events.index'));
    }

    public function test_unknown_email_gets_the_same_reset_message(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nadie@example.com'])
            ->assertSessionHas('message', 'Si el correo está registrado, te enviamos un enlace para elegir una contraseña nueva.');

        Notification::assertNothingSent();
    }

    public function test_too_many_login_attempts_return_to_the_form(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

        $payload = [
            'email' => 'ana@example.com',
            'password' => 'incorrecta',
        ];

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post(route('login'), $payload);
        }

        $this->post(route('login'), $payload)
            ->assertRedirect()
            ->assertSessionHas('message', 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.');
    }
}
