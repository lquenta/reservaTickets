<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EventMedia extends Model
{
    public const KIND_PHOTO = 'photo';

    public const KIND_VIDEO = 'video';

    public const SOURCE_UPLOAD = 'upload';

    public const SOURCE_FACEBOOK = 'facebook';

    protected $fillable = [
        'event_id',
        'kind',
        'source',
        'file_path',
        'thumbnail_path',
        'external_url',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (EventMedia $media) {
            if ($media->file_path) {
                Storage::disk('public')->delete($media->file_path);
            }
            if ($media->thumbnail_path) {
                Storage::disk('public')->delete($media->thumbnail_path);
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isVideo(): bool
    {
        return $this->kind === self::KIND_VIDEO;
    }

    public function isPhoto(): bool
    {
        return $this->kind === self::KIND_PHOTO;
    }

    public function fileUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return asset('storage/'.$this->file_path);
    }

    public function thumbnailUrl(): ?string
    {
        if ($this->thumbnail_path) {
            return asset('storage/'.$this->thumbnail_path);
        }

        if ($this->isPhoto() && $this->file_path) {
            return $this->fileUrl();
        }

        return null;
    }

    public function embedUrl(?int $width = null, ?int $height = null): ?string
    {
        if ($this->source !== self::SOURCE_FACEBOOK || ! $this->external_url) {
            return null;
        }

        if ($this->isVideo()) {
            $query = [
                'href' => $this->external_url,
                'show_text' => 'false',
            ];
            if ($width !== null) {
                $query['width'] = max(220, $width);
                $query['height'] = $height ?? (int) round($query['width'] * 16 / 9);
            }

            return 'https://www.facebook.com/plugins/video.php?'.http_build_query($query);
        }

        return 'https://www.facebook.com/plugins/post.php?'.http_build_query([
            'href' => $this->external_url,
            'show_text' => 'false',
            'width' => $width ?? 500,
        ]);
    }

    public function sourceLabel(): string
    {
        if ($this->source === self::SOURCE_FACEBOOK) {
            return $this->isVideo() ? 'Reel de Facebook' : 'Foto de Facebook';
        }

        return $this->isVideo() ? 'Video subido' : 'Foto subida';
    }

    /**
     * @return array{kind: string, url: string}|null
     */
    public static function classifyFacebookUrl(?string $url): ?array
    {
        $video = FeaturedVideo::canonicalizeFacebookUrl($url);
        if ($video) {
            return ['kind' => self::KIND_VIDEO, 'url' => $video];
        }

        $photo = self::canonicalizeFacebookPhotoUrl($url);
        if ($photo) {
            return ['kind' => self::KIND_PHOTO, 'url' => $photo];
        }

        return null;
    }

    public static function canonicalizeFacebookPhotoUrl(?string $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || FeaturedVideo::isShareUrl($url) || FeaturedVideo::canonicalizeFacebookUrl($url)) {
            return null;
        }

        if (! preg_match('~^https?://(?:www\.|m\.|web\.)?(?:facebook\.com|fb\.com)/~i', $url)) {
            return null;
        }

        $isPhoto = preg_match('~facebook\.com/(?:photo\.php|photo/|permalink\.php)~i', $url)
            || preg_match('~facebook\.com/[^/?#]+/(?:photos|posts)/~i', $url)
            || preg_match('~[?&]fbid=\d+~i', $url);

        if (! $isPhoto) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['path'])) {
            return null;
        }

        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $keep = array_intersect_key($query, array_flip(['fbid', 'set', 'type', 'story_fbid', 'id']));
        $canonical = 'https://www.facebook.com'.$parts['path'];
        if ($keep !== []) {
            $canonical .= '?'.http_build_query($keep);
        }

        return $canonical;
    }
}
