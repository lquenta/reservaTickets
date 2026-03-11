<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Models\ReservationTicket;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    private function getReportData(): array
    {
        $ticketsSoldTotal = ReservationTicket::query()
            ->whereHas('reservation', fn ($q) => $q->where('status', Reservation::STATUS_CONFIRMADO))
            ->count();

        $ticketsSoldByEvent = ReservationTicket::query()
            ->join('reservations', 'reservation_tickets.reservation_id', '=', 'reservations.id')
            ->where('reservations.status', Reservation::STATUS_CONFIRMADO)
            ->select('reservations.event_id', DB::raw('COUNT(*) as total'))
            ->groupBy('reservations.event_id')
            ->get()
            ->keyBy('event_id');

        $eventsForTickets = Event::whereIn('id', $ticketsSoldByEvent->keys())->orderBy('starts_at', 'desc')->get()->keyBy('id');

        $clientsWithTickets = User::query()
            ->whereHas('reservations', fn ($q) => $q->where('status', Reservation::STATUS_CONFIRMADO))
            ->with([
                'reservations' => fn ($q) => $q->where('status', Reservation::STATUS_CONFIRMADO)->with(['event', 'reservationTickets.seat']),
            ])
            ->orderBy('name')
            ->get();

        $ticketsByEvent = ReservationTicket::query()
            ->join('reservations', 'reservation_tickets.reservation_id', '=', 'reservations.id')
            ->where('reservations.status', Reservation::STATUS_CONFIRMADO)
            ->select('reservations.event_id', DB::raw('COUNT(*) as tickets_sold'))
            ->groupBy('reservations.event_id')
            ->get();

        $eventIds = $ticketsByEvent->pluck('event_id')->unique()->values()->all();
        $eventsWithPrice = Event::with(['sections', 'ticketTemplate'])->whereIn('id', $eventIds)->get()->keyBy('id');

        // Tickets confirmados con reserva y evento (para eventos con secciones: precio por sección)
        $confirmedTickets = ReservationTicket::query()
            ->whereHas('reservation', fn ($q) => $q->where('status', Reservation::STATUS_CONFIRMADO)->whereIn('event_id', $eventIds))
            ->with(['seat', 'reservation' => fn ($q) => $q->select('id', 'event_id')->with(['event' => fn ($q) => $q->with('sections')])])
            ->get();

        $salesByEvent = $ticketsByEvent->map(function ($row) use ($eventsWithPrice, $confirmedTickets) {
            $event = $eventsWithPrice->get($row->event_id);
            $ticketsSold = (int) $row->tickets_sold;
            $total = 0.0;
            $unitPrice = 0.0;

            if ($event && $event->hasSections()) {
                $eventTickets = $confirmedTickets->filter(fn ($t) => $t->reservation && (int) $t->reservation->event_id === (int) $row->event_id);
                foreach ($eventTickets as $ticket) {
                    $eventSection = null;
                    if ($ticket->seat) {
                        $seat = $ticket->seat;
                        if ($seat->section_id) {
                            $eventSection = $event->sections->firstWhere('id', $seat->section_id);
                        }
                        if (! $eventSection && $event->sections) {
                            foreach ($event->sections as $es) {
                                if (! $es->has_seats) {
                                    continue;
                                }
                                if ($es->row_start !== null && $es->row_end !== null && $seat->row >= $es->row_start && $seat->row <= $es->row_end) {
                                    $eventSection = $es;
                                    break;
                                }
                            }
                        }
                        if (! $eventSection && $event->sections) {
                            $eventSection = $event->sections->where('has_seats', true)->first();
                        }
                    } else {
                        $eventSection = $ticket->section_id ? $event->sections->firstWhere('id', $ticket->section_id) : null;
                    }
                    if ($eventSection && $eventSection->pivot && $eventSection->pivot->price !== null) {
                        $total += (float) $eventSection->pivot->price;
                    }
                }
                $unitPrice = $ticketsSold > 0 ? $total / $ticketsSold : 0.0;
            } else {
                $unitPrice = $event && $event->ticketTemplate ? (float) $event->ticketTemplate->price : 0.0;
                $total = $ticketsSold * $unitPrice;
            }

            return (object) [
                'event_id' => $row->event_id,
                'event_name' => $event ? $event->name : 'Evento #'.$row->event_id,
                'tickets_sold' => $ticketsSold,
                'unit_price' => $unitPrice,
                'total' => $total,
            ];
        });

        $salesTotal = $salesByEvent->sum('total');

        // Clientes por evento: eventos con reservas CONFIRMADO y lista de clientes por evento
        $eventsWithClients = Event::query()
            ->whereHas('reservations', fn ($q) => $q->where('status', Reservation::STATUS_CONFIRMADO))
            ->with([
                'reservations' => fn ($q) => $q->where('status', Reservation::STATUS_CONFIRMADO)->with(['user', 'reservationTickets.seat']),
            ])
            ->orderBy('starts_at', 'desc')
            ->get();

        return compact(
            'ticketsSoldTotal',
            'ticketsSoldByEvent',
            'eventsForTickets',
            'clientsWithTickets',
            'salesByEvent',
            'salesTotal',
            'eventsWithClients'
        );
    }

    public function index(): View
    {
        $data = $this->getReportData();
        return view('admin.reports.index', $data);
    }

    public function downloadEntradasPdf(): Response
    {
        $data = $this->getReportData();
        $pdf = Pdf::loadView('admin.reports.pdf.entradas', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('reporte-entradas-vendidas-' . now()->format('Y-m-d') . '.pdf');
    }

    public function downloadClientesPdf(): Response
    {
        $data = $this->getReportData();
        $pdf = Pdf::loadView('admin.reports.pdf.clientes', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('reporte-clientes-' . now()->format('Y-m-d') . '.pdf');
    }

    public function downloadVentasPdf(): Response
    {
        $data = $this->getReportData();
        $pdf = Pdf::loadView('admin.reports.pdf.ventas', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('reporte-ventas-' . now()->format('Y-m-d') . '.pdf');
    }

    public function downloadClientesPorEventoPdf(): Response
    {
        $data = $this->getReportData();
        $pdf = Pdf::loadView('admin.reports.pdf.clientes-por-evento', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('reporte-clientes-por-evento-' . now()->format('Y-m-d') . '.pdf');
    }

    public function audit(Request $request): View
    {
        $query = ReservationAuditLog::with(['user', 'event', 'reservation'])
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('result')) {
            $query->where('result', $request->result);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->withQueryString();
        $events = Event::orderBy('name')->get(['id', 'name']);
        $usersWithLogs = User::whereHas('reservationAuditLogs')->orderBy('name')->get(['id', 'name', 'email']);

        $actionLabels = [
            ReservationAuditLog::ACTION_RESERVATION_ATTEMPT => 'Intento de reserva',
            ReservationAuditLog::ACTION_RESERVATION_CREATED => 'Reserva creada',
            ReservationAuditLog::ACTION_CHECKOUT_CONFIRMED => 'Checkout confirmado',
            ReservationAuditLog::ACTION_AUTHORIZED => 'Autorizada (admin)',
            ReservationAuditLog::ACTION_REJECTED => 'Rechazada (admin)',
        ];

        return view('admin.reports.audit', compact('logs', 'events', 'usersWithLogs', 'actionLabels'));
    }

    public function downloadAuditPdf(Request $request): Response
    {
        $query = ReservationAuditLog::with(['user', 'event', 'reservation'])
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('result')) {
            $query->where('result', $request->result);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->limit(500)->get();

        $actionLabels = [
            ReservationAuditLog::ACTION_RESERVATION_ATTEMPT => 'Intento de reserva',
            ReservationAuditLog::ACTION_RESERVATION_CREATED => 'Reserva creada',
            ReservationAuditLog::ACTION_CHECKOUT_CONFIRMED => 'Checkout confirmado',
            ReservationAuditLog::ACTION_AUTHORIZED => 'Autorizada (admin)',
            ReservationAuditLog::ACTION_REJECTED => 'Rechazada (admin)',
        ];

        $pdf = Pdf::loadView('admin.reports.pdf.audit', compact('logs', 'actionLabels'));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('reporte-auditoria-reservas-' . now()->format('Y-m-d') . '.pdf');
    }
}
