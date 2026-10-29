<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventMedia;
use App\Models\FeaturedVideo;
use App\Models\SiteContent;
use App\Models\TeamMember;
use App\Support\HomeShowcase;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $now = now();
        $featured_events = Event::where('is_active', true)
            ->orderByRaw('CASE WHEN starts_at >= ? THEN 0 ELSE 1 END', [$now])
            ->orderByRaw('CASE WHEN starts_at >= ? THEN starts_at END', [$now])
            ->orderByDesc('starts_at')
            ->take(3)
            ->get();

        $quienes_somos = SiteContent::quienesSomos();
        $hero_content = SiteContent::hero();
        $showcase = HomeShowcase::slides($hero_content);
        $team_members = TeamMember::orderBy('sort_order')->orderBy('id')->get();
        $featured_videos = FeaturedVideo::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(FeaturedVideo::MAX_ON_HOME)
            ->get()
            ->filter(fn (FeaturedVideo $video) => $video->embedUrl())
            ->values();

        $past_events = Event::query()
            ->past()
            ->withCount([
                'media as photos_count' => fn ($query) => $query->where('kind', EventMedia::KIND_PHOTO),
                'media as videos_count' => fn ($query) => $query->where('kind', EventMedia::KIND_VIDEO),
            ])
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();

        return view('home', compact('featured_events', 'showcase', 'quienes_somos', 'team_members', 'featured_videos', 'past_events'));
    }
}
