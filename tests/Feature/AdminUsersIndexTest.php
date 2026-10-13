<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
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

    public function test_searching_user_by_name_shows_reservation_details(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = User::factory()->create(['name' => 'María Búsqueda']);
        $other = User::factory()->create(['name' => 'Otro Usuario']);

        $event = Event::create([
            'name' => 'Concierto NOVA',
            'starts_at' => now()->addDays(5),
            'venue' => 'Sala Principal',
            'payment_code_prefix' => 'NV',
            'is_active' => true,
        ]);
        $otherEvent = Event::create([
            'name' => 'Evento Ajeno',
            'starts_at' => now()->addDays(8),
            'venue' => 'Otra sala',
            'payment_code_prefix' => 'AJ',
            'is_active' => true,
        ]);

        $reservation = Reservation::create([
            'user_id' => $target->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'NV-USER-01',
            'sale_amount' => 150,
            'confirmed_payment_at' => now(),
        ]);
        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'holder_name' => 'María Titular',
            'position' => 1,
        ]);

        Reservation::create([
            'user_id' => $other->id,
            'event_id' => $otherEvent->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'AJ-OTHER-01',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'María Búsqueda']))
            ->assertOk()
            ->assertSee('María Búsqueda')
            ->assertSee('Concierto NOVA')
            ->assertSee('Confirmado')
            ->assertSee('NV-USER-01')
            ->assertSee('María Titular')
            ->assertSee('150,00 Bs')
            ->assertDontSee('Otro Usuario')
            ->assertDontSee('Evento Ajeno');
    }

    public function test_searching_user_without_reservations_shows_empty_message(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['name' => 'María Búsqueda']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'María Búsqueda']))
            ->assertOk()
            ->assertSee('María Búsqueda')
            ->assertSee('Este usuario no tiene reservas.');
    }

    public function test_users_index_without_search_shows_reservation_count_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = User::factory()->create(['name' => 'María Búsqueda']);
        $event = Event::create([
            'name' => 'Concierto NOVA',
            'starts_at' => now()->addDays(5),
            'venue' => 'Sala Principal',
            'payment_code_prefix' => 'NV',
            'is_active' => true,
        ]);
        Reservation::create([
            'user_id' => $target->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'NV-USER-01',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('María Búsqueda')
            ->assertSee('1 reserva')
            ->assertDontSee('Código NV-USER-01');
    }

    public function test_users_index_is_admin_only(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
