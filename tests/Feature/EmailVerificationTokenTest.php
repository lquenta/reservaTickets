<?php

namespace Tests\Feature;

use App\Models\EmailVerificationToken;
use App\Models\User;
use App\Notifications\VerifyEmail;
use App\Services\EmailVerificationTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTokenTest extends TestCase
{
    use RefreshDatabase;

    private EmailVerificationTokenService $tokens;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokens = app(EmailVerificationTokenService::class);
    }

    public function test_valid_token_verifies_email_and_redirects_logged_in_user(): void
    {
        $user = User::factory()->unverified()->create();
        $plain = $this->tokens->issue($user);

        $this->actingAs($user)
            ->get(route('verification.verify-token', ['token' => $plain]))
            ->assertRedirect(route('home').'?verified=1');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_valid_token_verifies_email_for_guest(): void
    {
        $user = User::factory()->unverified()->create();
        $plain = $this->tokens->issue($user);

        $this->get(route('verification.verify-token', ['token' => $plain]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_expired_token_fails(): void
    {
        $user = User::factory()->unverified()->create();
        $plain = $this->tokens->issue($user);

        EmailVerificationToken::query()
            ->where('user_id', $user->id)
            ->update(['expires_at' => now()->subMinute()]);

        $this->get(route('verification.verify-token', ['token' => $plain]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_used_token_fails(): void
    {
        $user = User::factory()->unverified()->create();
        $plain = $this->tokens->issue($user);

        $this->tokens->verify($plain);

        $this->get(route('verification.verify-token', ['token' => $plain]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_invalid_token_fails(): void
    {
        $user = User::factory()->unverified()->create();
        $this->tokens->issue($user);

        $this->get(route('verification.verify-token', ['token' => str_repeat('x', 64)]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_invalidates_previous_unused_token(): void
    {
        $user = User::factory()->unverified()->create();
        $first = $this->tokens->issue($user);
        $second = $this->tokens->issue($user);

        $this->get(route('verification.verify-token', ['token' => $first]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertNull($user->fresh()->email_verified_at);

        $this->get(route('verification.verify-token', ['token' => $second]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_notification_uses_opaque_token_url_only(): void
    {
        $user = User::factory()->unverified()->create();

        $mail = (new VerifyEmail)->toMail($user);
        $url = $mail->viewData['url'];

        $this->assertStringContainsString('/email/verify-token/', $url);
        $this->assertStringNotContainsString('signature=', $url);
        $this->assertStringNotContainsString('expires=', $url);
        $this->assertStringNotContainsString('hash=', $url);
    }

    public function test_legacy_signed_route_still_works(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('home').'?verified=1');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
