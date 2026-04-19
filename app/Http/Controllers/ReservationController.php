<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Services\ReservationAuditService;
use App\Services\ReservationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(): View
    {
        $reservations = auth()->user()->reservations()->with('event')->latest()->paginate(10);

        return view('reservations.index', compact('reservations'));
    }

    public function create(Event $event): View|RedirectResponse
    {
        if (! $event->is_active || $event->starts_at->isPast()) {
            return redirect()->route('events.index')->with('message', 'Este evento no está disponible.');
        }

        $event->load('sections.seats', 'venue.seats', 'venue.layoutElements.seat');

        $seats = [];
        $seatsMap = [];
        $availableSeatIds = [];
        $blockedSeatIds = [];
        $sectionsData = [];
        $layoutElements = [];
        $seatIdToPrice = [];
        $sectionIdToPrice = [];
        $sectionIdToName = [];
        $layoutCanvas = ['width' => null, 'height' => null];

        if ($event->venue_id) {
            $venue = $event->getRelationValue('venue');
            if ($venue) {
                $layoutCanvas = [
                    'width' => $venue->layout_canvas_width,
                    'height' => $venue->layout_canvas_height,
                ];
                $blockedSeatIds = $event->blockedSeatIds()->flip();
                $layoutElements = $venue->layoutElements->map(function ($element) use ($blockedSeatIds) {
                    $seat = $element->seat;
                    $isBlocked = false;
                    if ($seat) {
                        $isBlocked = (bool) $seat->blocked || $blockedSeatIds->has($seat->id);
                    }

                    return [
                        'id' => $element->id,
                        'type' => $element->type,
                        'seat_id' => $element->seat_id,
                        'x' => (float) $element->x,
                        'y' => (float) $element->y,
                        'w' => (float) $element->w,
                        'h' => (float) $element->h,
                        'rotation' => (float) $element->rotation,
                        'z_index' => (int) $element->z_index,
                        'meta' => $element->meta ?? [],
                        'seat' => $seat ? [
                            'id' => $seat->id,
                            'label' => $seat->display_label,
                            'row' => $seat->row,
                            'number' => $seat->number,
                            'section_id' => $seat->section_id,
                            'blocked' => $isBlocked,
                        ] : null,
                    ];
                })->values()->all();
                if ($event->hasSections()) {
                    foreach ($event->sections as $section) {
                        $pivot = $section->pivot;
                        $price = $pivot->price;
                        if ($section->has_seats) {
                            $sectionSeats = $section->seats()->orderBy('row')->orderBy('number')->get()->map(function ($seat) use ($blockedSeatIds) {
                                $seat->blocked = (bool) $seat->blocked || $blockedSeatIds->has($seat->id);

                                return $seat;
                            });
                            if ($sectionSeats->isEmpty() && $section->row_start !== null && $section->row_end !== null) {
                                $fallbackSeats = $venue->seats();
                                $section->applySeatSpatialConstraints($fallbackSeats);
                                $sectionSeats = $fallbackSeats->orderBy('row')->orderBy('number')->get()->map(function ($seat) use ($blockedSeatIds) {
                                    $seat->blocked = (bool) $seat->blocked || $blockedSeatIds->has($seat->id);

                                    return $seat;
                                });
                            }
                            if ($sectionSeats->isEmpty()) {
                                $sectionSeats = $venue->seats()->orderBy('row')->orderBy('number')->get()->map(function ($seat) use ($blockedSeatIds) {
                                    $seat->blocked = (bool) $seat->blocked || $blockedSeatIds->has($seat->id);

                                    return $seat;
                                });
                            }
                            $sectionAvailableIds = $event->availableSeats($section->id)->pluck('id')->all();
                            if (empty($sectionAvailableIds) && $section->row_start !== null && $section->row_end !== null) {
                                $avail = $event->availableSeats(null);
                                $section->applySeatSpatialConstraints($avail);
                                $sectionAvailableIds = $avail->pluck('id')->all();
                            }
                            if (empty($sectionAvailableIds)) {
                                $sectionAvailableIds = $event->availableSeats(null)->pluck('id')->all();
                            }
                            $sectionsData[] = [
                                'id' => $section->id,
                                'name' => $section->name,
                                'price' => $price,
                                'has_seats' => true,
                                'seats' => $sectionSeats,
                                'availableSeatIds' => array_values($sectionAvailableIds),
                            ];
                        } else {
                            $availableCapacity = $event->availableCapacityForSection($section);
                            $sectionsData[] = [
                                'id' => $section->id,
                                'name' => $section->name,
                                'price' => $price,
                                'has_seats' => false,
                                'capacity' => $section->capacity,
                                'availableCapacity' => $availableCapacity,
                            ];
                        }
                    }
                    $seatsMap = [];
                    $seatIdToPrice = [];
                    $sectionIdToPrice = [];
                    $sectionIdToName = [];
                    foreach ($sectionsData as $sd) {
                        $sectionIdToPrice[$sd['id']] = $sd['price'] ?? null;
                        $sectionIdToName[$sd['id']] = $sd['name'];
                        if (! empty($sd['seats'])) {
                            foreach ($sd['seats'] as $seat) {
                                $seatsMap[$seat->id] = $seat->display_label;
                                $seatIdToPrice[$seat->id] = $sd['price'] ?? null;
                            }
                        }
                    }
                } else {
                    $seats = $venue->seats()->orderBy('row')->orderBy('number')->get()->map(function ($seat) use ($blockedSeatIds) {
                        $seat->blocked = (bool) $seat->blocked || $blockedSeatIds->has($seat->id);

                        return $seat;
                    });
                    $seatsMap = $seats->keyBy('id')->map(fn ($s) => ['label' => $s->display_label])->all();
                    $availableSeatIds = $event->availableSeats()->pluck('id')->all();
                    $seatIdToPrice = [];
                    $sectionIdToPrice = [];
                    $sectionIdToName = [];
                }
            }
        }

        return view('reservations.create', compact('event', 'seats', 'seatsMap', 'availableSeatIds', 'sectionsData', 'seatIdToPrice', 'sectionIdToPrice', 'sectionIdToName', 'layoutElements', 'layoutCanvas'));
    }

    public function store(StoreReservationRequest $request, ReservationService $service): RedirectResponse
    {
        $event = Event::findOrFail($request->validated('event_id'));
        if (! $event->is_active || $event->starts_at->isPast()) {
            return redirect()->route('events.index')->with('message', 'Este evento no está disponible.');
        }

        $singleName = $request->boolean('single_name');

        if ($event->venue_id) {
            $seatIds = array_map('intval', $request->validated('seat_ids', []));
            if ($event->hasSections()) {
                $sectionQuantities = $request->validated('section_quantities', []);
                $reservation = $service->createReservationWithSections(
                    auth()->user(),
                    $event,
                    $seatIds,
                    is_array($sectionQuantities) ? $sectionQuantities : [],
                    $request->all(),
                    $singleName
                );
            } else {
                $count = count($seatIds);
                $names = $singleName
                    ? array_fill(0, $count, $request->validated('holder_name'))
                    : array_map(fn ($i) => $request->validated("holder_name_{$i}", ''), range(1, max(1, $count)));
                $seatAssignments = null;
                if (! $singleName && $count > 0) {
                    $seatAssignments = array_map(fn ($i) => (int) $request->validated("seat_for_{$i}"), range(1, $count));
                }
                $reservation = $service->createReservation(auth()->user(), $event, $seatIds, $singleName, $names, $seatAssignments);
            }
        } else {
            $quantity = (int) $request->validated('quantity');
            $names = $singleName
                ? [$request->validated('holder_name')]
                : array_map(fn ($i) => $request->validated("holder_name_{$i}"), range(1, $quantity));
            $reservation = $service->createReservationWithoutSeats(auth()->user(), $event, $quantity, $singleName, $names);
        }

        app(ReservationAuditService::class)->log(
            ReservationAuditLog::ACTION_RESERVATION_CREATED,
            ReservationAuditLog::RESULT_SUCCESS,
            auth()->user(),
            $event,
            $reservation
        );

        return redirect()->route('checkout.show', $reservation);
    }

    public function seats(Event $event): JsonResponse
    {
        if (! $event->venue_id) {
            return response()->json(['seats' => [], 'venue' => null]);
        }
        $event->load('venue.layoutElements.seat');
        $venue = $event->getRelationValue('venue');
        if (! $venue) {
            return response()->json(['seats' => [], 'venue' => null]);
        }
        $availableIds = $event->availableSeats()->pluck('id')->flip();
        $blockedByEvent = $event->blockedSeatIds()->flip();
        $seats = $venue->seats()->orderBy('row')->orderBy('number')->get()->map(function ($seat) use ($availableIds, $blockedByEvent) {
            $isBlocked = (bool) $seat->blocked || $blockedByEvent->has($seat->id);

            return [
                'id' => $seat->id,
                'row' => $seat->row,
                'row_letter' => $seat->row_letter,
                'number' => $seat->number,
                'label' => $seat->display_label,
                'blocked' => $isBlocked,
                'available' => ! $isBlocked && $availableIds->has($seat->id),
            ];
        });

        return response()->json([
            'seats' => $seats,
            'layout' => $venue->layoutElements->map(function ($element) use ($availableIds, $blockedByEvent) {
                $seat = $element->seat;
                $isBlocked = false;
                $isAvailable = false;
                if ($seat) {
                    $isBlocked = (bool) $seat->blocked || $blockedByEvent->has($seat->id);
                    $isAvailable = ! $isBlocked && $availableIds->has($seat->id);
                }

                return [
                    'id' => $element->id,
                    'type' => $element->type,
                    'seat_id' => $element->seat_id,
                    'x' => (float) $element->x,
                    'y' => (float) $element->y,
                    'w' => (float) $element->w,
                    'h' => (float) $element->h,
                    'rotation' => (float) $element->rotation,
                    'z_index' => (int) $element->z_index,
                    'meta' => $element->meta ?? [],
                    'seat' => $seat ? [
                        'id' => $seat->id,
                        'label' => $seat->display_label,
                        'row' => $seat->row,
                        'number' => $seat->number,
                        'section_id' => $seat->section_id,
                        'blocked' => $isBlocked,
                        'available' => $isAvailable,
                    ] : null,
                ];
            })->values(),
            'venue' => [
                'name' => $venue->name,
                'plan_image_path' => $venue->plan_image_path,
                'layout_canvas_width' => $venue->layout_canvas_width,
                'layout_canvas_height' => $venue->layout_canvas_height,
            ],
        ]);
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403);
        }
        if ($reservation->status === Reservation::STATUS_INICIADO && $reservation->isExpired()) {
            $reservation->update(['status' => Reservation::STATUS_CANCELADO]);
        }

        return redirect()->route('home')->with('message', 'Tiempo agotado');
    }

    public function downloadTicketsPdf(Request $request, Reservation $reservation): Response
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403);
        }
        if ($reservation->status !== Reservation::STATUS_CONFIRMADO) {
            abort(404, 'Solo puedes descargar los tickets de reservas confirmadas.');
        }

        $reservation->load(['event.ticketTemplate', 'reservationTickets.seat', 'reservationTickets.section']);
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

        $filename = 'tickets-'.$reservation->payment_code.'.pdf';
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }
}
