<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;
    public function test_privacy_policy_is_linked_from_home_and_describes_google_data(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('privacy'), false)
            ->assertSee('Política de privacidad');

        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('POLÍTICA DE PRIVACIDAD')
            ->assertSee('NOVA')
            ->assertSee('identificador de tu cuenta de Google')
            ->assertSee('No vendemos datos de usuarios de Google')
            ->assertSee('13 de octubre de 2026');
    }
}
