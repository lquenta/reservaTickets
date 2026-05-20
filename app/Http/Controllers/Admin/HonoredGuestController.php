<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\AdminSaleContext;
use App\Http\Controllers\Controller;
use App\Http\Concerns\CreatesAdminSaleReservation;
use App\Http\Requests\Admin\AdminStoreAdminSaleReservationRequest;
use App\Http\Requests\Admin\AdminSurrogateClientRequest;
use App\Jobs\NotifyAdminNewReservationJob;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Models\User;
use App\Services\ClientProvisioningService;
use App\Services\ReservationAuditService;
use App\Services\ReservationService;
use App\Support\EventSeatSelectionData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HonoredGuestController extends Controller
{
    use CreatesAdminSaleReservation;

    public const SESSION_CLIENT_ID = 'admin_honored.client_user_id';

    public function lookup(Request $request, Event $event, ClientProvisioningService $provisioning): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $user = $provisioning->lookupByEmail($request->input('email'));

        if (! $user) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'email_verified' => $user->hasVerifiedEmail(),
        ]);
    }

    public function create(Event $event): View|RedirectResponse
    {
        if (! $event->acceptsReservations()) {
            return redirect()->route('admin.events.index')->with('message', 'Este evento no acepta reservas.');
        }

        return view('admin.sales.honored-guest.client', compact('event'));
    }

    public function start(AdminSurrogateClientRequest $request, Event $event, ClientProvisioningService $provisioning): RedirectResponse
    {
        if (! $event->acceptsReservations()) {
            return redirect()->route('admin.events.index')->with('message', 'Este evento no acepta reservas.');
        }

        $resolution = $provisioning->resolveForAdminSale(
            $request->validated('client_name'),
            $request->validated('client_email'),
            $request->validated('client_phone'),
            $request->user(),
            User::PROVISIONED_VIA_HONORED_GUEST,
            $request->boolean('update_existing_profile')
        );

        session([
            self::SESSION_CLIENT_ID => $resolution->user->id,
            'admin_honored.event_id' => $event->id,
        ]);

        return redirect()->route('admin.events.honored-guest.seats', $event);
    }

    public function seats(Event $event): View|RedirectResponse
    {
        $clientId = session(self::SESSION_CLIENT_ID);
        if (! $clientId || (int) session('admin_honored.event_id') !== (int) $event->id) {
            return redirect()->route('admin.events.honored-guest.create', $event)
                ->with('message', 'Primero ingresa los datos del invitado.');
        }

        $client = User::findOrFail($clientId);

        return view('reservations.create', array_merge(
            EventSeatSelectionData::withZeroPrices(EventSeatSelectionData::build($event)),
            [
                'event' => $event,
                'client' => $client,
                'storeRoute' => route('admin.events.honored-guest.store', $event),
                'layout' => 'layouts.admin',
                'contentSection' => 'admin',
                'isAdminSale' => true,
                'isHonoredGuest' => true,
            ]
        ));
    }

    public function store(
        AdminStoreAdminSaleReservationRequest $request,
        Event $event,
        ReservationService $service
    ): RedirectResponse {
        $clientId = session(self::SESSION_CLIENT_ID);
        if (! $clientId) {
            return redirect()->route('admin.events.honored-guest.create', $event);
        }

        if (! $event->acceptsReservations()) {
            return redirect()->route('admin.events.index')->with('message', 'Este evento no acepta reservas.');
        }

        $client = User::findOrFail($clientId);

        $adminSale = new AdminSaleContext(
            soldBy: $request->user(),
            saleType: Reservation::SALE_TYPE_HONORED_GUEST,
            initialStatus: Reservation::STATUS_PENDIENTE_PAGO,
            expiryMinutes: 1440,
        );

        $request->merge(['event_id' => $event->id, 'single_name' => $request->boolean('single_name', true)]);
        if ($request->boolean('single_name', true) && ! $request->filled('holder_name')) {
            $request->merge(['holder_name' => $client->name]);
        }

        $reservation = $this->createReservationFromRequest($request, $client, $event, $adminSale, $service);

        app(ReservationAuditService::class)->log(
            ReservationAuditLog::ACTION_HONORED_GUEST_CREATED,
            ReservationAuditLog::RESULT_SUCCESS,
            $request->user(),
            $event,
            $reservation,
            $client,
            'Invitado de honor registrado (pendiente autorización).'
        );

        NotifyAdminNewReservationJob::dispatch($reservation->fresh())->onConnection('database');

        session()->forget([self::SESSION_CLIENT_ID, 'admin_honored.event_id']);

        return redirect()->route('admin.reservations.index')
            ->with('message', 'Invitado de honor registrado. Pendiente de autorización.');
    }
}
