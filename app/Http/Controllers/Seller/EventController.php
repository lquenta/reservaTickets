<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\EventSeatOverviewMapData;
use Illuminate\Http\RedirectResponse;
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

    public function seats(Event $event): View|RedirectResponse
    {
        if (! $event->acceptsReservations()) {
            return redirect()->route('seller.events.index')
                ->with('message', 'Este evento no acepta reservas.');
        }

        $mapData = EventSeatOverviewMapData::forEvent($event);
        if ($mapData === null) {
            return redirect()->route('seller.events.index')
                ->with('error', 'Este evento no tiene butacas.');
        }

        return view('seller.events.seats', array_merge([
            'event' => $event,
            'backUrl' => route('seller.events.index'),
        ], $mapData));
    }
}
