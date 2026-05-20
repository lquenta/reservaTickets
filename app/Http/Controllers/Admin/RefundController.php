<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use App\Services\RefundService;
use App\Support\SeatLabelSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(Request $request): View
    {
        $events = Event::query()->orderByDesc('starts_at')->get(['id', 'name', 'starts_at']);
        $eventId = (int) $request->integer('event_id');
        $selectedEvent = $eventId ? Event::query()->find($eventId) : null;

        $reservations = collect();
        if ($selectedEvent) {
            $query = Reservation::query()
                ->where('event_id', $selectedEvent->id)
                ->where('status', Reservation::STATUS_CONFIRMADO)
                ->with([
                    'user',
                    'event',
                    'soldBy',
                    'reservationTickets.seat',
                    'reservationTickets.section',
                ]);

            if ($request->filled('q')) {
                $search = $request->string('q')->trim()->toString();
                $term = '%'.$search.'%';
                $query->where(function ($q) use ($term, $search) {
                    $q->where('payment_code', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u
                            ->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term))
                        ->orWhereHas('reservationTickets', function ($t) use ($term, $search) {
                            $t->where('holder_name', 'like', $term)
                                ->orWhereHas('seat', function ($s) use ($search) {
                                    SeatLabelSearch::applyToSeatQuery($s, $search);
                                });
                        });
                });
            }

            $reservations = $query->latest()->paginate(10)->withQueryString();
        }

        return view('admin.refunds.index', compact('events', 'selectedEvent', 'eventId', 'reservations'));
    }

    public function refund(Request $request, Reservation $reservation, RefundService $refundService): RedirectResponse
    {
        $validated = $request->validate([
            'refund_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $reservation->load('reservationTickets');

        if ($reservation->hasValidatedTickets()) {
            return back()->with('error', 'No se puede reembolsar: al menos una entrada ya fue validada en puerta.');
        }

        try {
            $refundService->refund($reservation, $request->user(), $validated['refund_reason'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $redirect = $request->input('redirect', route('admin.refunds.index', ['event_id' => $reservation->event_id]));

        return redirect()->to($redirect)->with('message', 'Reserva reembolsada. Las butacas quedan liberadas.');
    }
}
