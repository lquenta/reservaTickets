<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    public function test_login_page_offers_google_when_it_is_configured(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Continuar con Google')
            ->assertSee(route('auth.google.redirect'), false);
    }

    public function test_google_callback_creates_a_verified_account_without_the_registration_form(): void
    {
        $this->fakeGoogleUser();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('events.index'));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ana.google@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('gid-1', $user->google_id);
        $this->assertSame('Ana Google', $user->name);
        $this->assertSame(User::PROVISIONED_VIA_GOOGLE, $user->provisioned_via);
        $this->assertFalse($user->isGuest());
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(1, User::query()->count());
    }

    public function test_google_callback_links_an_existing_account_with_the_same_email(): void
    {
        $existing = User::factory()->unverified()->create([
            'email' => 'ana.google@gmail.com',
            'name' => 'Ana Local',
        ]);

        $this->fakeGoogleUser();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('events.index'));

        $existing->refresh();
        $this->assertAuthenticatedAs($existing);
        $this->assertSame('gid-1', $existing->google_id);
        $this->assertNotNull($existing->email_verified_at);
        $this->assertSame(1, User::query()->count());
    }

    public function test_google_callback_rejects_an_unverified_email(): void
    {
        $this->fakeGoogleUser(['email_verified' => false]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    private function fakeGoogleUser(array $overrides = []): void
    {
        $user = SocialiteUser::fake(array_merge([
            'id' => 'gid-1',
            'name' => 'Ana Google',
            'email' => 'ana.google@gmail.com',
            'email_verified' => true,
        ], $overrides));

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($user);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }
}
