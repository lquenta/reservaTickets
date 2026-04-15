<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Section;
use App\Models\Seat;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::orderBy('starts_at', 'desc')->paginate(15);
        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        $venues = Venue::orderBy('name')->get();
        return view('admin.events.create', compact('venues'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'venue' => ['required', 'string', 'max:255'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'payment_code_prefix' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'qr_image' => ['nullable', 'image', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $event = new Event;
        $event->name = $validated['name'];
        $event->description = $validated['description'] ?? null;
        $event->starts_at = $validated['starts_at'];
        $event->venue = $validated['venue'];
        $event->venue_id = $validated['venue_id'] ?? null;
        $event->payment_code_prefix = $validated['payment_code_prefix'] ?? null;
        $event->is_active = $request->boolean('is_active');
        $event->save();

        if ($request->hasFile('qr_image')) {
            $path = $request->file('qr_image')->store('event-qr', 'public');
            $event->update(['qr_image_path' => $path]);
        }
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('event-covers', 'public');
            $event->update(['cover_image_path' => $path]);
        }

        return redirect()->route('admin.events.index')->with('message', 'Evento creado correctamente.');
    }

    public function edit(Event $event): View
    {
        $event->load('sections');
        $venues = Venue::with('sections')->orderBy('name')->get();
        return view('admin.events.edit', compact('event', 'venues'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'venue' => ['required', 'string', 'max:255'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'payment_code_prefix' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'qr_image' => ['nullable', 'image', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'event_sections' => ['nullable', 'array'],
            'event_sections.*.section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'event_sections.*.use' => ['nullable', 'boolean'],
            'event_sections.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $event->name = $validated['name'];
        $event->description = $validated['description'] ?? null;
        $event->starts_at = $validated['starts_at'];
        $event->venue = $validated['venue'];
        $event->venue_id = $validated['venue_id'] ?? null;
        $event->payment_code_prefix = $validated['payment_code_prefix'] ?? null;
        $event->is_active = $request->boolean('is_active');

        if ($request->hasFile('qr_image')) {
            $path = $request->file('qr_image')->store('event-qr', 'public');
            $event->qr_image_path = $path;
        }
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('event-covers', 'public');
            $event->cover_image_path = $path;
        }
        $event->save();

        $this->syncEventSections($event, $request->input('event_sections', []));

        return redirect()->route('admin.events.index')->with('message', 'Evento actualizado.');
    }

    private function syncEventSections(Event $event, array $eventSectionsInput): void
    {
        if (! $event->venue_id) {
            $event->sections()->detach();
            return;
        }
        $venueId = $event->venue_id;
        $sync = [];
        $sortOrder = 0;
        foreach ($eventSectionsInput as $input) {
            if (! is_array($input)) {
                continue;
            }
            $sectionId = isset($input['section_id']) ? (int) $input['section_id'] : 0;
            if (! $sectionId || empty($input['use'] ?? false)) {
                continue;
            }
            $section = Section::where('id', $sectionId)->where('venue_id', $venueId)->first();
            if (! $section) {
                continue;
            }
            $price = isset($input['price']) && $input['price'] !== '' ? (float) $input['price'] : null;
            $sync[$section->id] = ['price' => $price, 'sort_order' => $sortOrder++];
        }
        $event->sections()->sync($sync);
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();
        return redirect()->route('admin.events.index')->with('message', 'Evento eliminado.');
    }

    public function markSoldOut(Event $event): RedirectResponse
    {
        if (! $event->is_active) {
            return redirect()->route('admin.events.index')->with('message', 'El evento ya estaba marcado como SOLD OUT.');
        }

        $event->update(['is_active' => false]);

        return redirect()->route('admin.events.index')->with('message', 'Evento marcado como SOLD OUT. Se bloquearon nuevas reservas.');
    }

    public function reopenSales(Event $event): RedirectResponse
    {
        if ($event->is_active) {
            return redirect()->route('admin.events.index')->with('message', 'El evento ya está habilitado.');
        }

        $event->update(['is_active' => true]);

        return redirect()->route('admin.events.index')->with('message', 'Evento habilitado nuevamente para reservas.');
    }

    public function seats(Event $event): View|RedirectResponse
    {
        if (! $event->venue_id) {
            return redirect()->route('admin.events.index')->with('error', 'Este evento no tiene butacas.');
        }

        $event->load('venue.seats');
        $venue = $event->getRelationValue('venue');
        if (! $venue) {
            return redirect()->route('admin.events.index')->with('error', 'Lugar no encontrado.');
        }

        $seats = $venue->seats()->orderBy('row')->orderBy('number')->get();
        $seatsByRow = $seats->groupBy('row');
        $occupiedSeatIds = $event->occupiedSeatIds()->flip();
        $blockedSeatIds = $event->blockedSeatIds()->flip();

        return view('admin.events.seats', compact('event', 'seatsByRow', 'occupiedSeatIds', 'blockedSeatIds'));
    }

    public function blockSeat(Event $event, Seat $seat): RedirectResponse
    {
        if (! $this->seatBelongsToEventVenue($event, $seat)) {
            return redirect()->route('admin.events.seats', $event)->with('error', 'La butaca no pertenece al lugar del evento.');
        }

        $occupiedSeatIds = $event->occupiedSeatIds()->flip();
        if ($occupiedSeatIds->has($seat->id)) {
            return redirect()->route('admin.events.seats', $event)->with('error', 'No se puede bloquear una butaca ocupada.');
        }

        $event->blockedSeats()->syncWithoutDetaching([$seat->id]);

        return redirect()->route('admin.events.seats', $event)->with('message', 'Butaca bloqueada para este evento.');
    }

    public function unblockSeat(Event $event, Seat $seat): RedirectResponse
    {
        if (! $this->seatBelongsToEventVenue($event, $seat)) {
            return redirect()->route('admin.events.seats', $event)->with('error', 'La butaca no pertenece al lugar del evento.');
        }

        $event->blockedSeats()->detach($seat->id);

        return redirect()->route('admin.events.seats', $event)->with('message', 'Butaca desbloqueada para este evento.');
    }

    private function seatBelongsToEventVenue(Event $event, Seat $seat): bool
    {
        return (int) $event->venue_id > 0 && (int) $seat->venue_id === (int) $event->venue_id;
    }
}
