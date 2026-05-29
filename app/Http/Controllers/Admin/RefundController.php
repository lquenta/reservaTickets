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
                ->whereHas('reservationTickets', fn ($t) => $t->active()->whereNull('validated_at'))
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
            'ticket_ids' => ['nullable', 'array', 'min:1'],
            'ticket_ids.*' => ['integer'],
        ]);

        $reservation->load('reservationTickets');

        try {
            if (! empty($validated['ticket_ids'])) {
                $updated = $refundService->refundTickets(
                    $reservation,
                    $request->user(),
                    $validated['ticket_ids'],
                    $validated['refund_reason'] ?? null
                );
            } else {
                if ($reservation->hasValidatedTickets()) {
                    return back()->with('error', 'No se puede reembolsar la reserva completa: al menos una entrada ya fue validada en puerta. Selecciona solo las entradas reembolsables.');
                }

                $updated = $refundService->refund($reservation, $request->user(), $validated['refund_reason'] ?? null);
            }
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $redirect = $request->input('redirect', route('admin.refunds.index', ['event_id' => $reservation->event_id]));

        $message = $updated->status === Reservation::STATUS_CONFIRMADO
            ? 'Reembolso parcial registrado. Las butacas seleccionadas quedan liberadas.'
            : 'Reserva reembolsada. Las butacas quedan liberadas.';

        return redirect()->to($redirect)->with('message', $message);
    }
}
