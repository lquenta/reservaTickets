<?php

namespace Tests\Feature;

use App\Models\Seat;
use App\Models\Section;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionSeatRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_venue_update_rejects_overlapping_seat_sections(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $venue = Venue::create([
            'name' => 'Test Venue Sections',
            'slug' => 'test-venue-sections',
            'address' => 'X',
            'seat_rows' => 4,
            'seat_columns' => 4,
        ]);
        $this->seedSeats($venue);

        $response = $this->actingAs($admin)->put(route('admin.venues.update', $venue), [
            'name' => $venue->name,
            'slug' => $venue->slug,
            'address' => $venue->address,
            'seat_rows' => 4,
            'seat_columns' => 4,
            'sections' => [
                [
                    'id' => '',
                    'name' => 'Zona A',
                    'has_seats' => '1',
                    'row_start' => 1,
                    'row_end' => 2,
                    'col_start' => 1,
                    'col_end' => 2,
                ],
                [
                    'id' => '',
                    'name' => 'Zona B',
                    'has_seats' => '1',
                    'row_start' => 2,
                    'row_end' => 3,
                    'col_start' => 2,
                    'col_end' => 3,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('sections');
    }

    public function test_venue_assigns_seats_by_row_and_column_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $venue = Venue::create([
            'name' => 'Venue Col Range',
            'slug' => 'venue-col-range',
            'address' => 'Y',
            'seat_rows' => 3,
            'seat_columns' => 5,
        ]);
        $this->seedSeats($venue);

        $this->actingAs($admin)->put(route('admin.venues.update', $venue), [
            'name' => $venue->name,
            'slug' => $venue->slug,
            'address' => $venue->address,
            'seat_rows' => 3,
            'seat_columns' => 5,
            'sections' => [
                [
                    'id' => '',
                    'name' => 'Lateral izq',
                    'has_seats' => '1',
                    'row_start' => 1,
                    'row_end' => 2,
                    'col_start' => 1,
                    'col_end' => 2,
                ],
            ],
        ])->assertRedirect(route('admin.venues.edit', $venue));

        $sectionId = $venue->fresh()->sections()->first()->id;

        $this->assertDatabaseHas('seats', [
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 1,
            'section_id' => $sectionId,
        ]);
        $this->assertDatabaseHas('seats', [
            'venue_id' => $venue->id,
            'row' => 2,
            'number' => 2,
            'section_id' => $sectionId,
        ]);
        $this->assertDatabaseHas('seats', [
            'venue_id' => $venue->id,
            'row' => 1,
            'number' => 3,
            'section_id' => null,
        ]);
    }

    public function test_venue_update_preserves_section_id_on_seats_outside_submitted_ranges(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $venue = Venue::create([
            'name' => 'Venue Preserve Layout Sections',
            'slug' => 'venue-preserve-layout-sections',
            'address' => 'Z',
            'seat_rows' => 4,
            'seat_columns' => 4,
        ]);
        $this->seedSeats($venue);

        $layoutSection = Section::create([
            'venue_id' => $venue->id,
            'name' => 'Solo layout',
            'slug' => 'solo-layout',
            'sort_order' => 0,
            'has_seats' => true,
            'row_start' => 1,
            'row_end' => 1,
            'col_start' => 1,
            'col_end' => 1,
        ]);

        $outsideSeat = Seat::query()->where('venue_id', $venue->id)->where('row', 4)->where('number', 4)->firstOrFail();
        $outsideSeat->update(['section_id' => $layoutSection->id]);

        $this->actingAs($admin)->put(route('admin.venues.update', $venue), [
            'name' => $venue->name,
            'slug' => $venue->slug,
            'address' => $venue->address,
            'seat_rows' => 4,
            'seat_columns' => 4,
            'sections' => [
                [
                    'id' => (string) $layoutSection->id,
                    'name' => $layoutSection->name,
                    'has_seats' => '1',
                    'row_start' => 1,
                    'row_end' => 2,
                    'col_start' => 1,
                    'col_end' => 2,
                ],
            ],
        ])->assertRedirect(route('admin.venues.edit', $venue));

        $outsideSeat->refresh();
        $this->assertSame($layoutSection->id, $outsideSeat->section_id);
    }

    private function seedSeats(Venue $venue): void
    {
        $rows = (int) $venue->seat_rows;
        $cols = (int) $venue->seat_columns;
        for ($row = 1; $row <= $rows; $row++) {
            for ($num = 1; $num <= $cols; $num++) {
                $letter = $row >= 1 && $row <= 26 ? chr(64 + $row) : (string) $row;
                Seat::create([
                    'venue_id' => $venue->id,
                    'row' => $row,
                    'number' => $num,
                    'label' => $letter.'-'.$num,
                    'blocked' => false,
                ]);
            }
        }
    }
}
