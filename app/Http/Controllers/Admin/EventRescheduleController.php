<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use App\Services\EventRescheduleService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventRescheduleController extends Controller
{
    public function create(Event $event): View
    {
        $confirmedCount = $event->reservations()->where('status', Reservation::STATUS_CONFIRMADO)->count();
        $pendingCount = $event->reservations()->where('status', Reservation::STATUS_PENDIENTE_PAGO)->count();

        return view('admin.events.reschedule', compact('event', 'confirmedCount', 'pendingCount'));
    }

    public function store(Request $request, Event $event, EventRescheduleService $service): RedirectResponse
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStartsAt = Carbon::parse($validated['starts_at']);

        if ($event->starts_at->equalTo($newStartsAt)) {
            return redirect()->route('admin.events.show', $event)
                ->with('message', 'La nueva fecha es igual a la actual.');
        }

        $service->reschedule($event, $newStartsAt, $request->user(), $validated['reason'] ?? null);

        return redirect()->route('admin.events.show', $event)
            ->with('message', 'Evento reprogramado correctamente.');
    }
}
