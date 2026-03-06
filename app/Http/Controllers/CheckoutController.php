<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Services\ReservationAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Reservation $reservation): View|RedirectResponse
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403);
        }
        if ($reservation->status !== Reservation::STATUS_INICIADO) {
            return redirect()->route('home')->with('message', 'Esta reserva ya fue procesada.');
        }
        if ($reservation->isExpired()) {
            $reservation->update(['status' => Reservation::STATUS_CANCELADO]);
            return redirect()->route('home')->with('message', 'Tiempo agotado');
        }

        $reservation->load(['event.sections', 'event.ticketTemplate', 'reservationTickets.seat', 'reservationTickets.section']);
        $totalPrice = 0;
        if ($reservation->event->hasSections()) {
            foreach ($reservation->reservationTickets as $ticket) {
                $eventSection = null;
                if ($ticket->seat) {
                    $sectionId = $ticket->seat->section_id;
                    if ($sectionId) {
                        $eventSection = $reservation->event->sections->firstWhere('id', $sectionId);
                    }
                    if (! $eventSection && $ticket->seat) {
                        foreach ($reservation->event->sections as $es) {
                            if (! $es->has_seats) {
                                continue;
                            }
                            if ($es->row_start !== null && $es->row_end !== null && $ticket->seat->row >= $es->row_start && $ticket->seat->row <= $es->row_end) {
                                $eventSection = $es;
                                break;
                            }
                        }
                    }
                    if (! $eventSection) {
                        $eventSection = $reservation->event->sections->where('has_seats', true)->first();
                    }
                } else {
                    $eventSection = $ticket->section_id
                        ? $reservation->event->sections->firstWhere('id', $ticket->section_id)
                        : null;
                }
                if ($eventSection && $eventSection->pivot && $eventSection->pivot->price !== null) {
                    $totalPrice += (float) $eventSection->pivot->price;
                }
            }
        } else {
            $template = $reservation->event->ticketTemplate;
            $unitPrice = $template ? (float) $template->price : 0;
            $totalPrice = $unitPrice * $reservation->reservationTickets->count();
        }
        return view('checkout.show', compact('reservation', 'totalPrice'));
    }

    public function confirm(Request $request, Reservation $reservation): RedirectResponse
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403);
        }
        if ($reservation->status !== Reservation::STATUS_INICIADO || $reservation->isExpired()) {
            return redirect()->route('home')->with('message', 'La reserva expiró o ya fue procesada.');
        }

        $request->validate([
            'accept_terms' => ['required', 'accepted'],
            'payment_receipt' => ['required', 'image', 'max:5120'], // 5 MB
        ], [
            'payment_receipt.required' => 'Debe subir una captura o foto del comprobante de pago.',
            'payment_receipt.image' => 'El comprobante debe ser una imagen (JPG, PNG, etc.).',
            'payment_receipt.max' => 'La imagen no debe superar 5 MB.',
        ]);

        $path = $request->file('payment_receipt')->store('payment-receipts', 'public');

        $reservation->update([
            'status' => Reservation::STATUS_PENDIENTE_PAGO,
            'confirmed_payment_at' => now(),
            'payment_receipt_path' => $path,
        ]);

        $reservation->load('event');
        app(ReservationAuditService::class)->log(
            ReservationAuditLog::ACTION_CHECKOUT_CONFIRMED,
            ReservationAuditLog::RESULT_SUCCESS,
            $request->user(),
            $reservation->event,
            $reservation
        );

        return redirect()->route('reservations.index')->with('message', 'Reserva registrada. Recibirás los tickets por correo una vez se autorice el pago.');
    }
}
