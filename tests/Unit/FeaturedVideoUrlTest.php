<?php

namespace Tests\Unit;

use App\Models\FeaturedVideo;
use Tests\TestCase;

class FeaturedVideoUrlTest extends TestCase
{
    public function test_accepts_reel_url_and_builds_embed(): void
    {
        $canonical = FeaturedVideo::canonicalizeFacebookUrl('https://www.facebook.com/reel/1051618684397981');

        $this->assertSame('https://www.facebook.com/reel/1051618684397981', $canonical);

        $video = new FeaturedVideo(['video_url' => $canonical]);
        $this->assertStringContainsString('plugins/video.php', $video->embedUrl());
        $this->assertStringContainsString(rawurlencode($canonical), $video->embedUrl());
        $this->assertStringContainsString('show_text=false', $video->embedUrl());
    }

    public function test_rejects_share_short_link(): void
    {
        $url = 'https://www.facebook.com/share/r/1JnfP3u1io/';

        $this->assertTrue(FeaturedVideo::isShareUrl($url));
        $this->assertNull(FeaturedVideo::canonicalizeFacebookUrl($url));
        $this->assertFalse(FeaturedVideo::isAcceptedFacebookUrl($url));
    }

    public function test_accepts_watch_and_page_video_urls(): void
    {
        $this->assertSame(
            'https://www.facebook.com/watch/?v=123456',
            FeaturedVideo::canonicalizeFacebookUrl('https://www.facebook.com/watch/?v=123456')
        );
        $this->assertSame(
            'https://www.facebook.com/novapage/videos/987',
            FeaturedVideo::canonicalizeFacebookUrl('https://www.facebook.com/novapage/videos/987/')
        );
        $this->assertSame(
            'https://fb.watch/abcDE',
            FeaturedVideo::canonicalizeFacebookUrl('https://fb.watch/abcDE/')
        );
    }
}
