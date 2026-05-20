<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::query()
            ->where('is_active', true)
            ->orderByDesc('starts_at')
            ->get()
            ->filter(fn (Event $event) => $event->acceptsReservations());

        return view('seller.events.index', compact('events'));
    }
}
