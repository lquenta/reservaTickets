<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class EventGalleryController extends Controller
{
    public function show(Event $event): View
    {
        if ($event->starts_at === null || $event->starts_at->greaterThanOrEqualTo(now())) {
            abort(404);
        }

        $event->load(['media' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')]);

        return view('events.gallery', compact('event'));
    }
}
