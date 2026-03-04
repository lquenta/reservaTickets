<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendReservationTicketsJob;
use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Services\ReservationAuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Reservation::with(['user', 'event', 'reservationTickets.seat'])->latest();

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reservations = $query->paginate(15);
        return view('admin.reservations.index', compact('reservations'));
    }

    public function authorizeReservation(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status !== Reservation::STATUS_PENDIENTE_PAGO) {
            return redirect()->route('admin.reservations.index')->with('message', 'Solo se pueden autorizar reservas en estado PENDIENTE_PAGO.');
        }

        $reservation->update(['status' => Reservation::STATUS_CONFIRMADO]);
        SendReservationTicketsJob::dispatch($reservation);

        return redirect()->route('admin.reservations.index')->with('message', 'Reserva autorizada. Se han enviado los tickets por correo.');
    }

    public function rejectReservation(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status !== Reservation::STATUS_PENDIENTE_PAGO) {
            return redirect()->route('admin.reservations.index')->with('message', 'Solo se pueden rechazar reservas en estado Pendiente de pago.');
        }

        $reservation->update(['status' => Reservation::STATUS_CANCELADO]);

        $reservation->load('event');
        app(ReservationAuditService::class)->log(
            ReservationAuditLog::ACTION_REJECTED,
            ReservationAuditLog::RESULT_SUCCESS,
            auth()->user(),
            $reservation->event,
            $reservation
        );

        return redirect()->route('admin.reservations.index')->with('message', 'Reserva rechazada. Las butacas quedan liberadas.');
    }

    public function ticketsPdf(Request $request, Reservation $reservation): Response
    {
        if ($reservation->status !== Reservation::STATUS_CONFIRMADO) {
            abort(404, 'Solo se puede ver el PDF de reservas confirmadas.');
        }

        $reservation->load(['event.ticketTemplate', 'reservationTickets.seat']);
        $template = $reservation->event->ticketTemplate;
        $design = $template ? $template->design : \App\Models\TicketTemplate::defaultDesign();
        $price = $template ? (float) $template->price : 0;

        $pdf = Pdf::loadView('tickets.pdf', [
            'reservation' => $reservation,
            'event' => $reservation->event,
            'tickets' => $reservation->reservationTickets,
            'design' => $design,
            'price' => $price,
        ]);

        $filename = 'tickets-' . $reservation->payment_code . '.pdf';
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }

    public function resendTickets(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status !== Reservation::STATUS_CONFIRMADO) {
            return redirect()->route('admin.reservations.index')->with('error', 'Solo se pueden reenviar tickets de reservas confirmadas.');
        }

        SendReservationTicketsJob::dispatch($reservation);

        return redirect()->route('admin.reservations.index')->with('message', 'Tickets reenviados por correo a ' . $reservation->user->email);
    }
}
