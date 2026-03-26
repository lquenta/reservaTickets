<?php

use App\Http\Controllers\Api\TicketValidationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Ticket validator - API key protected)
|--------------------------------------------------------------------------
|
| Only the ticket validation endpoint is exposed here. All routes in this
| file require the X-API-Key header (or Authorization: Bearer <key>).
|
*/

if (config('services.ticket_validator.enabled', true)) {
    Route::prefix('v1')->middleware('ticket.api.key')->group(function () {
        Route::post('tickets/validate', [TicketValidationController::class, 'validateTicket']);
    });
}
