<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventMedia;
use App\Models\FeaturedVideo;
use App\Models\HeroSetting;
use App\Models\HeroSlide;
use App\Support\HomeShowcase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeShowcaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_explains_the_product_when_there_is_no_media(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('Qué es NOVA', $html);
        $this->assertStringContainsString('Reserva entradas para los eventos', $html);
        $this->assertStringContainsString('id="como-funciona"', $html);
        $this->assertStringContainsString('Elige el evento', $html);
        $this->assertStringContainsString('Aparta tus lugares', $html);
        $this->assertStringContainsString('Paga y recibe el ticket', $html);
    }

    public function test_opening_slider_mixes_current_events_past_events_videos_and_photos(): void
    {
        $this->event('Luna Rosa', now()->addDays(12), 'Noche de neón en el teatro.');
        $past = $this->event('Eclipse Nocturno', now()->subDays(4));
        $past->update(['cover_image_path' => 'events/eclipse.jpg']);
        EventMedia::create([
            'event_id' => $past->id,
            'kind' => EventMedia::KIND_PHOTO,
            'source' => EventMedia::SOURCE_UPLOAD,
            'file_path' => 'event-media/eclipse.jpg',
            'sort_order' => 1,
        ]);
        FeaturedVideo::create([
            'title' => 'Spot de temporada',
            'video_url' => 'https://www.facebook.com/reel/1051618684397981',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        HeroSlide::create([
            'image_path' => 'hero-slides/promo.jpg',
            'sort_order' => 1,
        ]);
        HeroSetting::create([
            'type' => HeroSetting::TYPE_VIDEO,
            'video_path' => 'hero-video/adelanto.mp4',
        ]);

        $slides = HomeShowcase::slides();
        $kinds = array_column($slides, 'kind');

        $this->assertSame('intro', $kinds[0]);
        $this->assertContains('current', $kinds);
        $this->assertContains('past', $kinds);
        $this->assertContains('video', $kinds);
        $this->assertNotContains('promo', $kinds);

        $current = collect($slides)->firstWhere('kind', 'current');
        $archive = collect($slides)->firstWhere('kind', 'past');
        $this->assertSame('Noche de neón en el teatro.', $current['text']);
        $this->assertSame(route('events.gallery', $past), $archive['href']);
        $this->assertStringContainsString('events/eclipse.jpg', $archive['image']);

        $hero = $this->heroSection($this->get(route('home'))->assertOk()->getContent());

        $this->assertStringContainsString('Luna Rosa', $hero);
        $this->assertStringContainsString('En cartelera', $hero);
        $this->assertStringContainsString('Eclipse Nocturno', $hero);
        $this->assertStringContainsString('Ya pasó', $hero);
        $this->assertStringContainsString('events/eclipse.jpg', $hero);
        $this->assertStringContainsString('Spot de temporada', $hero);
        $this->assertStringContainsString('adelanto.mp4', $hero);
        $this->assertStringNotContainsString('hero-slides/promo.jpg', $hero);
    }

    public function test_slider_uses_promo_photos_when_the_hero_is_not_a_video(): void
    {
        HeroSlide::create([
            'image_path' => 'hero-slides/promo.jpg',
            'sort_order' => 1,
        ]);

        $promo = collect(HomeShowcase::slides())->firstWhere('kind', 'promo');
        $this->assertStringContainsString('hero-slides/promo.jpg', $promo['image']);

        $html = $this->heroSection($this->get(route('home'))->assertOk()->getContent());

        $this->assertStringContainsString('NOVA en vivo', $html);
        $this->assertStringContainsString('hero-slides/promo.jpg', $html);
    }

    public function test_long_event_copy_stops_on_a_word(): void
    {
        $this->event(
            'Upside Down',
            now()->subDay(),
            'NOVA te invita a sumergirte en una experiencia sonora inspirada en el soundtrack de todas las temporadas de Stranger Things. Prepárate para viajar entre sintetizadores y luces.'
        );

        $text = collect(HomeShowcase::slides())->firstWhere('title', 'Upside Down')['text'];

        $this->assertStringEndsWith('…', $text);
        $this->assertStringNotContainsString('sintet', $text);
        $this->assertLessThanOrEqual(141, mb_strlen($text));
    }

    private function event(string $name, \DateTimeInterface $startsAt, ?string $description = null): Event
    {
        return Event::create([
            'name' => $name,
            'description' => $description,
            'starts_at' => $startsAt,
            'venue' => 'Teatro Nova',
            'payment_code_prefix' => 'NV',
            'is_active' => true,
        ]);
    }

    private function heroSection(string $html): string
    {
        $start = strpos($html, 'id="hero"');
        $this->assertNotFalse($start);
        $end = strpos($html, 'id="como-funciona"', $start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }
}
