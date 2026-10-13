<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Models\Seat;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueLayoutElement;
use App\Support\OddEvenAislePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OddEvenAisleLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_save_drops_numbers_past_the_row_limit_and_keeps_an_unlabeled_filler(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $venue = $this->venueWithGrid(4, 10);
        $kept = $venue->seats()->where('row', 1)->where('number', 1)->firstOrFail();

        $this->actingAs($admin)
            ->putJson(route('admin.venues.layout.save', $venue), [
                'elements' => [
                    [
                        'type' => 'seat',
                        'seat_id' => $kept->id,
                        'x' => 40,
                        'y' => 80,
                        'w' => 52,
                        'h' => 52,
                        'z_index' => 1,
                        'meta' => ['source' => OddEvenAislePlan::SOURCE, 'row' => 1],
                    ],
                    [
                        'type' => 'unavailable',
                        'x' => 10,
                        'y' => 80,
                        'w' => 52,
                        'h' => 52,
                        'z_index' => 2,
                        'meta' => ['source' => OddEvenAislePlan::SOURCE, 'row' => 1],
                    ],
                ],
                'seat_row_limits' => ['1' => 6, '2' => 10],
            ])
            ->assertOk()
            ->assertJsonPath('elements.1.type', 'unavailable')
            ->assertJsonPath('elements.1.seat_id', null);

        $this->assertDatabaseMissing('seats', [
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 7,
        ]);
        $this->assertDatabaseHas('seats', [
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 6,
            'label' => 'A-6',
        ]);
        $this->assertDatabaseHas('seats', [
            'venue_id' => $venue->id,
            'row' => 2,
            'number' => 10,
            'label' => 'B-10',
        ]);
        $this->assertDatabaseHas('venue_layout_elements', [
            'venue_id' => $venue->id,
            'type' => VenueLayoutElement::TYPE_UNAVAILABLE,
            'seat_id' => null,
        ]);

        $this->actingAs($admin)->put(route('admin.venues.update', $venue), [
            'name' => $venue->name,
            'slug' => $venue->slug,
            'address' => $venue->address,
            'seat_rows' => 4,
            'seat_columns' => 10,
        ])->assertRedirect();

        $this->assertDatabaseMissing('seats', [
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 8,
        ]);
        $this->assertSame(6, $venue->seats()->where('row', 1)->count());
    }

    public function test_column_ranges_and_platea_sections_ignore_missing_numbers_and_fillers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $venue = $this->venueWithGrid(16, 24);
        $kept = $venue->seats()->where('row', 1)->where('number', 1)->firstOrFail();

        $this->actingAs($admin)
            ->putJson(route('admin.venues.layout.save', $venue), [
                'elements' => [
                    ['type' => 'seat', 'seat_id' => $kept->id, 'x' => 20, 'y' => 40, 'w' => 52, 'h' => 52, 'z_index' => 1],
                ],
                'seat_row_limits' => OddEvenAislePlan::domingo31RowLimits(),
            ])
            ->assertOk();

        $this->assertSame(350, $venue->seats()->count());
        $this->assertDatabaseMissing('seats', ['venue_id' => $venue->id, 'row' => 1, 'number' => 19]);
        $this->assertDatabaseHas('seats', ['venue_id' => $venue->id, 'row' => 7, 'number' => 24, 'label' => 'G-24']);

        $this->actingAs($admin)->put(route('admin.venues.update', $venue), [
            'name' => $venue->name,
            'slug' => $venue->slug,
            'address' => $venue->address,
            'seat_rows' => 16,
            'seat_columns' => 24,
            'sections' => [
                [
                    'name' => 'Platea VIP',
                    'has_seats' => '1',
                    'row_start' => 1,
                    'row_end' => 7,
                ],
                [
                    'name' => 'Platea General',
                    'has_seats' => '1',
                    'row_start' => 8,
                    'row_end' => 16,
                ],
                [
                    'name' => 'Galería',
                    'capacity' => 80,
                ],
            ],
        ])->assertRedirect(route('admin.venues.edit', $venue));

        $venue->refresh();
        $vip = $venue->sections()->where('name', 'Platea VIP')->firstOrFail();
        $general = $venue->sections()->where('name', 'Platea General')->firstOrFail();
        $gallery = $venue->sections()->where('name', 'Galería')->firstOrFail();

        $this->assertTrue($vip->has_seats);
        $this->assertSame(146, $vip->seats()->count());
        $this->assertSame(204, $general->seats()->count());
        $this->assertFalse($gallery->has_seats);
        $this->assertSame(80, $gallery->capacity);
        $this->assertSame(0, $gallery->seats()->count());
        $this->assertSame($vip->id, $venue->seats()->where('row', 1)->where('number', 1)->value('section_id'));
        $this->assertSame($vip->id, $venue->seats()->where('row', 1)->where('number', 2)->value('section_id'));
        $this->assertSame($general->id, $venue->seats()->where('row', 8)->where('number', 2)->value('section_id'));
        $this->assertSame(350, $venue->seats()->count());
    }

    public function test_column_range_selects_seat_numbers_on_both_sides_of_the_aisle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $venue = $this->venueWithGrid(2, 10);

        $this->actingAs($admin)
            ->putJson(route('admin.venues.layout.save', $venue), [
                'elements' => [
                    [
                        'type' => 'seat',
                        'seat_id' => $venue->seats()->where('row', 1)->where('number', 1)->value('id'),
                        'x' => 0,
                        'y' => 0,
                        'w' => 48,
                        'h' => 48,
                        'z_index' => 1,
                    ],
                ],
                'seat_row_limits' => ['1' => 6],
            ])
            ->assertOk();

        $this->actingAs($admin)->put(route('admin.venues.update', $venue), [
            'name' => $venue->name,
            'slug' => $venue->slug,
            'address' => $venue->address,
            'seat_rows' => 2,
            'seat_columns' => 10,
            'sections' => [
                [
                    'name' => 'Corte',
                    'has_seats' => '1',
                    'row_start' => 1,
                    'row_end' => 1,
                    'col_start' => 1,
                    'col_end' => 4,
                ],
            ],
        ])->assertRedirect();

        $sectionId = $venue->sections()->where('name', 'Corte')->value('id');
        $numbers = $venue->seats()->where('section_id', $sectionId)->orderBy('number')->pluck('number')->all();

        $this->assertSame([1, 2, 3, 4], $numbers);
        $this->assertNull($venue->seats()->where('row', 1)->where('number', 5)->value('section_id'));
        $this->assertDatabaseMissing('seats', ['venue_id' => $venue->id, 'row' => 1, 'number' => 7]);
    }

    public function test_row_limit_does_not_delete_a_seat_that_already_has_a_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $venue = $this->venueWithGrid(2, 8);
        $seat = $venue->seats()->where('row', 1)->where('number', 8)->firstOrFail();
        $event = Event::create([
            'name' => 'Evento pasillo',
            'description' => 'Desc',
            'starts_at' => now()->addDay(),
            'venue' => $venue->name,
            'venue_id' => $venue->id,
            'payment_code_prefix' => 'ODD',
            'is_active' => true,
        ]);
        $reservation = Reservation::create([
            'user_id' => $admin->id,
            'event_id' => $event->id,
            'status' => Reservation::STATUS_CONFIRMADO,
            'payment_code' => 'ODD-1',
        ]);
        ReservationTicket::create([
            'reservation_id' => $reservation->id,
            'seat_id' => $seat->id,
            'holder_name' => 'Ana',
            'position' => 1,
        ]);

        $this->actingAs($admin)
            ->putJson(route('admin.venues.layout.save', $venue), [
                'elements' => [
                    [
                        'type' => 'seat',
                        'seat_id' => $venue->seats()->where('row', 1)->where('number', 1)->value('id'),
                        'x' => 0,
                        'y' => 0,
                        'w' => 48,
                        'h' => 48,
                        'z_index' => 1,
                    ],
                ],
                'seat_row_limits' => ['1' => 4],
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('seats', [
            'id' => $seat->id,
            'row' => 1,
            'number' => 8,
        ]);
    }

    public function test_editor_explains_platea_sections_and_the_aisle_tool(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $venue = $this->venueWithGrid(16, 24);

        $this->actingAs($admin)
            ->get(route('admin.venues.edit', $venue))
            ->assertOk()
            ->assertSee('Insertar pasillo central')
            ->assertSee('Platea VIP')
            ->assertSee('Platea General')
            ->assertSee('Galería');
    }

    private function venueWithGrid(int $rows, int $columns): Venue
    {
        $venue = Venue::create([
            'name' => 'Sala '.$rows.'x'.$columns,
            'slug' => 'sala-'.$rows.'x'.$columns.'-'.uniqid(),
            'address' => 'Calle 1',
            'seat_rows' => $rows,
            'seat_columns' => $columns,
        ]);

        for ($row = 1; $row <= $rows; $row++) {
            $letter = chr(64 + $row);
            for ($number = 1; $number <= $columns; $number++) {
                Seat::create([
                    'venue_id' => $venue->id,
                    'row' => $row,
                    'number' => $number,
                    'label' => $letter.'-'.$number,
                    'blocked' => false,
                ]);
            }
        }

        return $venue;
    }
}
