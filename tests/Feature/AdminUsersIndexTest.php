<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_users_by_name_email_ci_phone_and_id(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = User::factory()->create([
            'name' => 'María Búsqueda',
            'email' => 'maria.busqueda@example.test',
            'ci' => '12345678',
            'phone' => '+56 9 1111 2222',
        ]);
        User::factory()->create([
            'name' => 'Otro Usuario',
            'email' => 'otro@example.test',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'María Búsqueda']))
            ->assertOk()
            ->assertSee('María Búsqueda')
            ->assertDontSee('otro@example.test');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'maria.busqueda@example.test']))
            ->assertOk()
            ->assertSee('María Búsqueda');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => '12345678']))
            ->assertOk()
            ->assertSee('María Búsqueda');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => '1111 2222']))
            ->assertOk()
            ->assertSee('María Búsqueda');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => (string) $target->id]))
            ->assertOk()
            ->assertSee('María Búsqueda')
            ->assertDontSee('otro@example.test');
    }

    public function test_users_index_is_admin_only(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
