<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::with('sections')
            ->where('is_active', true)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->paginate(12);

        return view('events.index', compact('events'));
    }
}
