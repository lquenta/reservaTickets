<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PastEventGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_past_events_from_newest_to_oldest(): void
    {
        $older = $this->event('Archivo antiguo', now()->subMonths(3));
        $newer = $this->event('Eclipse Nocturno', now()->subDays(4));
        $this->event('Próximo show', now()->addDays(10));

        $section = $this->pastSection($this->get(route('home'))->assertOk()->getContent());

        $this->assertTrue(strpos($section, 'Eclipse Nocturno') < strpos($section, 'Archivo antiguo'));
        $this->assertStringNotContainsString('Próximo show', $section);
    }

    public function test_gallery_shows_uploaded_and_facebook_media_only_for_past_events(): void
    {
        $past = $this->event('Eclipse Nocturno', now()->subDay());
        EventMedia::create([
            'event_id' => $past->id,
            'kind' => EventMedia::KIND_PHOTO,
            'source' => EventMedia::SOURCE_UPLOAD,
            'file_path' => 'event-media/foto.jpg',
            'sort_order' => 1,
        ]);
        EventMedia::create([
            'event_id' => $past->id,
            'kind' => EventMedia::KIND_VIDEO,
            'source' => EventMedia::SOURCE_FACEBOOK,
            'external_url' => 'https://www.facebook.com/reel/1051618684397981',
            'sort_order' => 2,
        ]);

        $this->get(route('events.gallery', $past))
            ->assertOk()
            ->assertSee('Eclipse Nocturno')
            ->assertSee('Foto subida')
            ->assertSee('Reel de Facebook')
            ->assertSee('1051618684397981', false);

        $upcoming = $this->event('Todavía no', now()->addDay());
        $this->get(route('events.gallery', $upcoming))->assertNotFound();
    }

    public function test_admin_can_add_a_photo_and_a_facebook_reel(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = $this->event('Pulso Violeta', now()->subDays(2));

        $this->actingAs($admin)
            ->post(route('admin.events.media.store', $event), [
                'mode' => 'photo',
                'files' => [UploadedFile::fake()->image('escena.jpg')],
            ])
            ->assertRedirect(route('admin.events.show', $event));

        $this->assertDatabaseHas('event_media', [
            'event_id' => $event->id,
            'kind' => EventMedia::KIND_PHOTO,
            'source' => EventMedia::SOURCE_UPLOAD,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.events.media.store', $event), [
                'mode' => 'facebook',
                'external_url' => 'https://www.facebook.com/reel/1051618684397981',
            ])
            ->assertRedirect(route('admin.events.show', $event));

        $this->assertDatabaseHas('event_media', [
            'event_id' => $event->id,
            'kind' => EventMedia::KIND_VIDEO,
            'source' => EventMedia::SOURCE_FACEBOOK,
            'external_url' => 'https://www.facebook.com/reel/1051618684397981',
        ]);
    }

    public function test_facebook_share_links_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = $this->event('Frecuencia 39', now()->subWeek());

        $this->actingAs($admin)
            ->from(route('admin.events.show', $event))
            ->post(route('admin.events.media.store', $event), [
                'mode' => 'facebook',
                'external_url' => 'https://www.facebook.com/share/abc123/',
            ])
            ->assertRedirect(route('admin.events.show', $event))
            ->assertSessionHasErrors('facebook_links.0');

        $this->assertDatabaseCount('event_media', 0);
    }

    public function test_admin_can_add_several_photos_and_facebook_links_at_once(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = $this->event('Neón Ritual', now()->subDays(3));

        $this->actingAs($admin)
            ->post(route('admin.events.media.store', $event), [
                'mode' => 'photo',
                'files' => [
                    UploadedFile::fake()->image('uno.jpg'),
                    UploadedFile::fake()->image('dos.jpg'),
                    UploadedFile::fake()->image('tres.jpg'),
                ],
            ])
            ->assertRedirect(route('admin.events.show', $event));

        $this->actingAs($admin)
            ->post(route('admin.events.media.store', $event), [
                'mode' => 'facebook',
                'external_urls' => "https://www.facebook.com/reel/11\nhttps://www.facebook.com/nova/photos/a.10/20/?type=3",
            ])
            ->assertRedirect(route('admin.events.show', $event));

        $this->assertSame(5, $event->media()->count());
        $this->assertSame(4, $event->media()->where('kind', EventMedia::KIND_PHOTO)->count());
        $this->assertSame(1, $event->media()->where('kind', EventMedia::KIND_VIDEO)->count());
    }

    public function test_photo_links_are_classified_apart_from_reels(): void
    {
        $photo = EventMedia::classifyFacebookUrl('https://m.facebook.com/nova/photos/a.10/20/?type=3');
        $reel = EventMedia::classifyFacebookUrl('https://www.facebook.com/reel/99');

        $this->assertSame(EventMedia::KIND_PHOTO, $photo['kind']);
        $this->assertSame('https://www.facebook.com/nova/photos/a.10/20/?type=3', $photo['url']);
        $this->assertSame(EventMedia::KIND_VIDEO, $reel['kind']);
        $this->assertNull(EventMedia::classifyFacebookUrl('https://www.facebook.com/share/xyz'));
    }

    private function event(string $name, \DateTimeInterface $startsAt): Event
    {
        return Event::create([
            'name' => $name,
            'starts_at' => $startsAt,
            'venue' => 'Teatro Nova',
            'payment_code_prefix' => 'NV',
            'is_active' => true,
        ]);
    }

    private function pastSection(string $html): string
    {
        $start = strpos($html, 'id="eventos-pasados"');
        $this->assertNotFalse($start);
        $end = strpos($html, 'id="contacto"', $start);

        return substr($html, $start, $end === false ? null : $end - $start);
    }
}
