<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FeaturedVideo extends Model
{
    public const MAX_ON_HOME = 3;

    protected $fillable = [
        'title',
        'video_url',
        'thumbnail_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function embedUrl(?int $width = null, ?int $height = null): ?string
    {
        $canonical = self::canonicalizeFacebookUrl($this->video_url);
        if (! $canonical) {
            return null;
        }

        $query = [
            'href' => $canonical,
            'show_text' => 'false',
        ];

        if ($width !== null) {
            $query['width'] = max(220, $width);
            $query['height'] = $height ?? (int) round($query['width'] * 16 / 9);
        }

        return 'https://www.facebook.com/plugins/video.php?'.http_build_query($query);
    }

    public function thumbnailUrl(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        return asset('storage/'.$this->thumbnail_path);
    }

    public static function isShareUrl(?string $url): bool
    {
        if (! $url) {
            return false;
        }

        return (bool) preg_match('~facebook\.com/share/~i', $url);
    }

    public static function isAcceptedFacebookUrl(?string $url): bool
    {
        return self::canonicalizeFacebookUrl($url) !== null;
    }

    public static function canonicalizeFacebookUrl(?string $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || self::isShareUrl($url)) {
            return null;
        }

        if (preg_match('~https?://(?:www\.|m\.|web\.)?facebook\.com/reel/(\d+)~i', $url, $m)) {
            return 'https://www.facebook.com/reel/'.$m[1];
        }

        if (preg_match('~https?://(?:www\.|m\.|web\.)?facebook\.com/([^/?#]+)/videos/(\d+)~i', $url, $m)) {
            return 'https://www.facebook.com/'.$m[1].'/videos/'.$m[2];
        }

        if (preg_match('~facebook\.com/watch/?.*[?&]v=(\d+)~i', $url, $m)) {
            return 'https://www.facebook.com/watch/?v='.$m[1];
        }

        if (preg_match('~https?://fb\.watch/([A-Za-z0-9_-]+)~i', $url, $m)) {
            return 'https://fb.watch/'.$m[1];
        }

        return null;
    }
}
