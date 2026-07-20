<?php

namespace App\Http\Concerns;

use App\DTOs\AdminSaleContext;
use App\Http\Requests\Admin\AdminStoreAdminSaleReservationRequest;
use App\Http\Requests\Admin\AdminSurrogateCheckoutRequest;
use App\Http\Requests\Admin\AdminSurrogateClientRequest;
use App\Jobs\NotifyAdminNewReservationJob;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use App\Models\User;
use App\Services\ClientProvisioningService;
use App\Services\ReservationAuditService;
use App\Services\ReservationPricingService;
use App\Services\ReservationService;
use App\Support\EventSeatSelectionData;
use App\Support\ReservationCheckoutMapData;
use App\Support\SurrogateSaleFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait ManagesSurrogateSale
{
    abstract protected function surrogateFlow(): SurrogateSaleFlow;

    public function create(Event $event): View|RedirectResponse
    {
        $flow = $this->surrogateFlow();

        if (! $event->acceptsReservations()) {
            return redirect()->route($flow->eventsIndexRoute)->with('message', 'Este evento no acepta reservas.');
        }

        return view('admin.sales.surrogate.client', [
            'event' => $event,
            'flow' => $flow,
        ]);
    }

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

    public function start(AdminSurrogateClientRequest $request, Event $event, ClientProvisioningService $provisioning): RedirectResponse
    {
        $flow = $this->surrogateFlow();

        if (! $event->acceptsReservations()) {
            return redirect()->route($flow->eventsIndexRoute)->with('message', 'Este evento no acepta reservas.');
        }

        $resolution = $provisioning->resolveForAdminSale(
            $request->validated('client_name'),
            $request->validated('client_email'),
            $request->validated('client_phone'),
            $request->user(),
            User::PROVISIONED_VIA_SURROGATE,
            $request->boolean('update_existing_profile'),
            $request->boolean('seller_will_deliver_tickets', true)
        );

        session([
            $flow->sessionClientKey => $resolution->user->id,
            $flow->sessionEventKey => $event->id,
        ]);

        return redirect()->to($flow->route('events.surrogate-sale.seats', $event));
    }

    public function seats(Event $event): View|RedirectResponse
    {
        $flow = $this->surrogateFlow();
        $clientId = session($flow->sessionClientKey);

        if (! $clientId || (int) session($flow->sessionEventKey) !== (int) $event->id) {
            return redirect()->to($flow->route('events.surrogate-sale.create', $event))
                ->with('message', 'Primero ingresa los datos del cliente.');
        }

        $client = User::findOrFail($clientId);

        if (! $event->acceptsReservations()) {
            return redirect()->route($flow->eventsIndexRoute)->with('message', 'Este evento no acepta reservas.');
        }

        return view('reservations.create', array_merge(EventSeatSelectionData::build($event), [
            'event' => $event,
            'client' => $client,
            'storeRoute' => $flow->route('events.surrogate-sale.store', $event),
            'layout' => $flow->layout,
            'contentSection' => $flow->contentSection,
            'isAdminSale' => true,
            'backUrl' => $flow->route('events.surrogate-sale.create', $event),
        ]));
    }

    public function store(
        AdminStoreAdminSaleReservationRequest $request,
        Event $event,
        ReservationService $service
    ): RedirectResponse {
        $flow = $this->surrogateFlow();
        $clientId = session($flow->sessionClientKey);

        if (! $clientId) {
            return redirect()->to($flow->route('events.surrogate-sale.create', $event));
        }

        $client = User::findOrFail($clientId);
        $adminSale = new AdminSaleContext(
            soldBy: $request->user(),
            saleType: Reservation::SALE_TYPE_SURROGATE,
        );

        $request->merge(['event_id' => $event->id, 'single_name' => $request->boolean('single_name', true)]);
        if ($request->boolean('single_name', true) && ! $request->filled('holder_name')) {
            $request->merge(['holder_name' => $client->name]);
        }

        $reservation = $this->createReservationFromRequest($request, $client, $event, $adminSale, $service);

        app(ReservationAuditService::class)->log(
            ReservationAuditLog::ACTION_SURROGATE_SALE_CREATED,
            ReservationAuditLog::RESULT_SUCCESS,
            $request->user(),
            $event,
            $reservation,
            $client,
            'Venta surrogada creada.'
        );

        return redirect()->to($flow->route('surrogate-sale.checkout', $reservation));
    }

    public function checkout(Reservation $reservation): View|RedirectResponse
    {
        $flow = $this->surrogateFlow();

        if (! $this->canManageSurrogate($reservation)) {
            abort(404);
        }

        if ($reservation->status !== Reservation::STATUS_INICIADO) {
            return redirect()->route($flow->checkoutSuccessRoute)->with('message', 'Esta reserva ya fue procesada.');
        }

        if ($reservation->isExpired()) {
            $reservation->update(['status' => Reservation::STATUS_CANCELADO]);

            return redirect()->to($flow->route('events.surrogate-sale.create', $reservation->event))
                ->with('message', 'La reserva expiró. Inicia de nuevo.');
        }

        $reservation->load(['event.sections', 'event.ticketTemplate', 'user', 'reservationTickets.seat', 'reservationTickets.section']);
        $pricing = app(ReservationPricingService::class);
        $totalPrice = $pricing->totalForReservation($reservation);
        $listTotalPrice = $pricing->listTotalForReservation($reservation);
        $presaleActive = $reservation->event->isPresaleActive()
            && $listTotalPrice > $totalPrice;
        $checkoutMap = ReservationCheckoutMapData::forReservation($reservation);

        return view('admin.sales.surrogate.checkout', compact('reservation', 'totalPrice', 'listTotalPrice', 'presaleActive', 'flow', 'checkoutMap'));
    }

    public function confirm(AdminSurrogateCheckoutRequest $request, Reservation $reservation): RedirectResponse
    {
        $flow = $this->surrogateFlow();

        if (! $this->canManageSurrogate($reservation)) {
            abort(404);
        }

        if ($reservation->status !== Reservation::STATUS_INICIADO || $reservation->isExpired()) {
            return redirect()->route($flow->checkoutSuccessRoute)->with('message', 'La reserva expiró o ya fue procesada.');
        }

        $path = $request->file('payment_receipt')->store('payment-receipts', 'public');

        $update = [
            'status' => Reservation::STATUS_PENDIENTE_PAGO,
            'confirmed_payment_at' => now(),
            'payment_receipt_path' => $path,
        ];

        $client = $reservation->user;
        if ($client?->isGuest()) {
            $update['seller_delivery_acknowledged_at'] = now();
            $update['seller_delivery_acknowledged_by_user_id'] = $request->user()->id;
        } elseif ($client && ! $client->hasVerifiedEmail() && $request->boolean('seller_delivery_responsibility')) {
            $update['seller_delivery_acknowledged_at'] = now();
            $update['seller_delivery_acknowledged_by_user_id'] = $request->user()->id;
        }

        $reservation->update($update);

        app(ReservationAuditService::class)->log(
            ReservationAuditLog::ACTION_SURROGATE_CHECKOUT_CONFIRMED,
            ReservationAuditLog::RESULT_SUCCESS,
            $request->user(),
            $reservation->event,
            $reservation,
            $client,
            'Checkout surrogada confirmado.'
        );

        if (! empty($update['seller_delivery_acknowledged_at'])) {
            app(ReservationAuditService::class)->log(
                ReservationAuditLog::ACTION_SURROGATE_DELIVERY_RESPONSIBILITY_ACCEPTED,
                ReservationAuditLog::RESULT_SUCCESS,
                $request->user(),
                $reservation->event,
                $reservation,
                $client,
                $client?->isGuest()
                    ? 'Entrega manual de tickets (invitado temporal).'
                    : 'Vendedor asumió responsabilidad de entrega (email no verificado).'
            );
        }

        $reservation = $reservation->fresh();
        NotifyAdminNewReservationJob::dispatch($reservation)->onConnection('database');

        session()->forget([$flow->sessionClientKey, $flow->sessionEventKey]);

        return redirect()->route($flow->checkoutSuccessRoute)
            ->with('message', 'Venta registrada. Pendiente de autorización.');
    }

    private function canManageSurrogate(Reservation $reservation): bool
    {
        return $reservation->sale_type === Reservation::SALE_TYPE_SURROGATE
            && $reservation->sold_by_user_id !== null;
    }
}
