<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\HeroSetting;
use App\Models\HeroSlide;
use App\Models\SiteContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured_events = Event::where('is_active', true)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->take(3)
            ->get();

        $hero_setting = HeroSetting::first();
        $hero_video_url = null;
        $hero_slides = [];

        if ($hero_setting && $hero_setting->isVideo()) {
            $hero_video_url = $hero_setting->getVideoSourceUrl();
        } else {
            $hero_slides = HeroSlide::orderBy('sort_order')->orderBy('id')->get()
                ->map(fn ($s) => asset('storage/'.$s->image_path))
                ->all();
        }

        $quienes_somos = SiteContent::quienesSomos();
        $hero_content = SiteContent::hero();

        return view('home', compact('featured_events', 'hero_video_url', 'hero_slides', 'quienes_somos', 'hero_content'));
    }
}
