<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $now = now();
        $events = Event::with('sections')
            ->listedPublicly()
            ->orderByDesc('is_active')
            ->orderByRaw('CASE WHEN starts_at >= ? THEN 0 ELSE 1 END', [$now])
            ->orderBy('starts_at')
            ->paginate(12);

        return view('events.index', compact('events'));
    }
}
