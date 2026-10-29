<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventMedia;
use App\Models\FeaturedVideo;
use App\Models\HeroSetting;
use App\Models\HeroSlide;
use App\Models\SiteContent;

class HomeShowcase
{
    public const CONTENT_LIMIT = 8;

    /**
     * Carrusel de portada: primero qué es NOVA, después una mezcla de
     * cartelera, videos, fotos promocionales y eventos pasados.
     *
     * @return list<array<string, mixed>>
     */
    public static function slides(?SiteContent $heroContent = null): array
    {
        $hero = HeroSetting::query()->first();
        $promoImages = self::promoImages($hero);
        $events = self::eventSlides();
        $past = self::pastSlides();
        $videos = array_values(array_filter([
            self::heroVideoSlide($hero),
            ...self::featuredVideoSlides(),
        ]));

        $story = self::mix([$events, $videos, $past, $promoImages], self::CONTENT_LIMIT);
        $backdrop = null;
        foreach ([...$events, ...$past, ...$promoImages] as $candidate) {
            if (! empty($candidate['image'])) {
                $backdrop = $candidate['image'];
                break;
            }
        }

        return [
            self::introSlide($heroContent, $backdrop, $story === [] ? self::heroVideoSlide($hero) : null),
            ...$story,
        ];
    }

    /**
     * @param  list<list<array<string, mixed>>>  $buckets
     * @return list<array<string, mixed>>
     */
    private static function mix(array $buckets, int $limit): array
    {
        $slides = [];
        $cursor = array_fill(0, count($buckets), 0);

        while (count($slides) < $limit) {
            $added = false;
            foreach ($buckets as $index => $items) {
                if ($cursor[$index] >= count($items)) {
                    continue;
                }
                $slides[] = $items[$cursor[$index]];
                $cursor[$index]++;
                $added = true;
                if (count($slides) >= $limit) {
                    break;
                }
            }
            if (! $added) {
                break;
            }
        }

        return $slides;
    }

    /**
     * @return array<string, mixed>
     */
    private static function introSlide(?SiteContent $heroContent, ?string $image, ?array $loneVideo): array
    {
        $phrase = trim((string) ($heroContent?->title ?: ''));
        if ($phrase === '') {
            $phrase = 'Tus entradas. Tu experiencia.';
        }

        $cta = trim((string) ($heroContent?->content ?: ''));
        if ($cta === '') {
            $cta = 'VER EVENTOS';
        }

        return self::slide([
            'kind' => 'intro',
            'badge' => 'Qué es NOVA',
            'title' => $phrase,
            'text' => 'Reserva entradas para los eventos: eliges el show, apartas de 1 a 10 lugares y recibes tus tickets por correo.',
            'image' => $loneVideo['image'] ?? $image,
            'video' => $loneVideo['video'] ?? null,
            'cta' => $cta,
            'href' => route('events.index'),
            'secondary' => 'Cómo funciona',
            'secondary_href' => route('home').'#como-funciona',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function eventSlides(): array
    {
        return Event::query()
            ->where('is_active', true)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->orderBy('id')
            ->take(4)
            ->get()
            ->map(function (Event $event) {
                $paused = (bool) $event->sales_paused;

                return self::slide([
                    'kind' => 'current',
                    'badge' => $event->isPresaleActive() ? 'Preventa' : 'En cartelera',
                    'title' => $event->name,
                    'text' => self::blurb($event->description, $paused
                        ? 'Las ventas de este evento están pausadas. Escríbenos y te contamos cómo entrar.'
                        : 'Ya puedes reservar tus entradas para este evento.'),
                    'meta' => self::whenWhere($event),
                    'image' => self::storageUrl($event->cover_image_path),
                    'cta' => $paused ? 'Consultar' : 'Reservar',
                    'href' => $paused ? route('home').'#contacto' : self::reserveUrl($event),
                ]);
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function pastSlides(): array
    {
        return Event::query()
            ->past()
            ->with(['media' => function ($query) {
                $query->where('kind', EventMedia::KIND_PHOTO);
            }])
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->take(3)
            ->get()
            ->map(function (Event $event) {
                return self::slide([
                    'kind' => 'past',
                    'badge' => 'Ya pasó',
                    'title' => $event->name,
                    'text' => self::blurb($event->description, 'Mira las fotos y los videos de esta fecha.'),
                    'meta' => self::whenWhere($event),
                    'image' => self::eventImage($event),
                    'cta' => 'Ver galería',
                    'href' => route('events.gallery', $event),
                ]);
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function featuredVideoSlides(): array
    {
        return FeaturedVideo::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(FeaturedVideo::MAX_ON_HOME)
            ->get()
            ->filter(fn (FeaturedVideo $video) => $video->embedUrl())
            ->map(function (FeaturedVideo $video) {
                $title = trim((string) $video->title);

                return self::slide([
                    'kind' => 'video',
                    'badge' => 'Video',
                    'title' => $title !== '' ? $title : 'Video promocional',
                    'text' => 'Un adelanto de lo que se vive en NOVA.',
                    'image' => $video->thumbnailUrl(),
                    'embed' => $video->embedUrl(),
                    'cta' => 'Ver videos',
                    'href' => route('home').'#videos',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function heroVideoSlide(?HeroSetting $hero): ?array
    {
        if (! $hero || ! $hero->isVideo()) {
            return null;
        }

        $url = $hero->getVideoSourceUrl();
        if (! $url) {
            return null;
        }

        $facebook = FeaturedVideo::canonicalizeFacebookUrl($url);
        if ($facebook) {
            $model = new FeaturedVideo(['video_url' => $facebook, 'title' => 'Video promocional']);

            return self::slide([
                'kind' => 'video',
                'badge' => 'Video',
                'title' => 'Video promocional',
                'text' => 'Mira el adelanto y entra a la cartelera.',
                'embed' => $model->embedUrl(),
                'cta' => 'Ver eventos',
                'href' => route('events.index'),
            ]);
        }

        if (! self::isFileVideo($url)) {
            return self::slide([
                'kind' => 'video',
                'badge' => 'Video',
                'title' => 'Video promocional',
                'text' => 'Mira el adelanto y entra a la cartelera.',
                'cta' => 'Ver video',
                'href' => $url,
                'blank' => true,
            ]);
        }

        return self::slide([
            'kind' => 'video',
            'badge' => 'Video',
            'title' => 'Así se vive',
            'text' => 'Un adelanto de la experiencia. Elige tu evento y reserva.',
            'video' => $url,
            'cta' => 'Ver eventos',
            'href' => route('events.index'),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function promoImages(?HeroSetting $hero): array
    {
        if ($hero && $hero->isVideo()) {
            return [];
        }

        return HeroSlide::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(3)
            ->get()
            ->map(function (HeroSlide $slide) {
                return self::slide([
                    'kind' => 'promo',
                    'badge' => 'En imágenes',
                    'title' => 'NOVA en vivo',
                    'text' => 'Fotos de la experiencia. Elige un evento y reserva tu lugar.',
                    'image' => self::storageUrl($slide->image_path),
                    'cta' => 'Ver eventos',
                    'href' => route('events.index'),
                ]);
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function slide(array $data): array
    {
        return array_merge([
            'kind' => 'promo',
            'badge' => '',
            'title' => '',
            'text' => '',
            'meta' => '',
            'image' => null,
            'video' => null,
            'embed' => null,
            'cta' => 'Ver eventos',
            'href' => route('events.index'),
            'secondary' => null,
            'secondary_href' => null,
            'blank' => false,
        ], $data);
    }

    private static function reserveUrl(Event $event): string
    {
        $user = auth()->user();
        if ($user && $user->isAdmin()) {
            return route('admin.dashboard');
        }
        if ($user) {
            return route('reservations.create', $event);
        }

        return route('reservations.entry', $event);
    }

    private static function whenWhere(Event $event): string
    {
        $when = $event->starts_at?->translatedFormat('l d F Y, H:i') ?? '';
        $place = trim((string) $event->getRawOriginal('venue'));

        return trim($when.($place !== '' ? ' · '.$place : ''));
    }

    private static function blurb(mixed $description, string $fallback): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $description)) ?? '');
        if ($text === '') {
            return $fallback;
        }

        $limit = 140;
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > 80) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " \t,.;:-").'…';
    }

    private static function eventImage(Event $event): ?string
    {
        if ($event->cover_image_path) {
            return self::storageUrl($event->cover_image_path);
        }

        $photo = $event->relationLoaded('media')
            ? $event->media->first(fn (EventMedia $media) => $media->thumbnailUrl())
            : null;

        return $photo?->thumbnailUrl();
    }

    private static function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return asset('storage/'.$path);
    }

    private static function isFileVideo(string $url): bool
    {
        return (bool) preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $url);
    }
}
