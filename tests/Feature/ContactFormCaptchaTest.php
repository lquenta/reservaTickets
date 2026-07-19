<?php

namespace Tests\Feature;

use App\Services\RecaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ContactFormCaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.recaptcha.site_key', 'contact-site-key');
        config()->set('services.recaptcha.secret_key', 'contact-secret-key');
    }

    public function test_public_contact_form_displays_recaptcha(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="g-recaptcha"', false)
            ->assertSee('data-sitekey="contact-site-key"', false);
    }

    public function test_contact_form_requires_recaptcha_when_configured(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Persona de prueba',
            'email' => 'persona@example.com',
            'message' => 'Necesito información.',
        ])
            ->assertRedirect(route('home').'#contacto')
            ->assertSessionHasErrors('g-recaptcha-response');
    }

    public function test_contact_form_rejects_an_invalid_recaptcha(): void
    {
        $this->mock(RecaptchaService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')
                ->once()
                ->withArgs(fn (string $token, ?string $ip): bool => $token === 'invalid-token' && $ip !== null)
                ->andReturnFalse();
        });

        $this->post(route('contact.store'), [
            'name' => 'Persona de prueba',
            'email' => 'persona@example.com',
            'message' => 'Necesito información.',
            'g-recaptcha-response' => 'invalid-token',
        ])
            ->assertRedirect(route('home').'#contacto')
            ->assertSessionHasErrors('g-recaptcha-response');
    }

    public function test_contact_form_accepts_a_valid_recaptcha(): void
    {
        $this->mock(RecaptchaService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')
                ->once()
                ->withArgs(fn (string $token, ?string $ip): bool => $token === 'valid-token' && $ip !== null)
                ->andReturnTrue();
        });

        $this->post(route('contact.store'), [
            'name' => 'Persona de prueba',
            'email' => 'persona@example.com',
            'message' => 'Necesito información.',
            'g-recaptcha-response' => 'valid-token',
        ])
            ->assertRedirect(route('home').'#contacto')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('message');
    }
}
