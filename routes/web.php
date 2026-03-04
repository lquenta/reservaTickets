<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('terminos-y-condiciones', function () {
    return view('legal.terms');
})->name('terms');

Route::get('events', [EventController::class, 'index'])->name('events.index');

Route::post('contact', [ContactController::class, 'store'])->name('contact.store');
Route::post('newsletter', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe')->middleware('throttle:5,1');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'can.reserve'])->group(function () {
    Route::get('reservations', [\App\Http\Controllers\ReservationController::class, 'index'])->name('reservations.index');
    Route::get('events/{event}/reserve', [\App\Http\Controllers\ReservationController::class, 'create'])->name('reservations.create');
    Route::get('events/{event}/seats', [\App\Http\Controllers\ReservationController::class, 'seats'])->name('reservations.seats');
    Route::post('reservations', [\App\Http\Controllers\ReservationController::class, 'store'])->name('reservations.store')->middleware('throttle:10,1');
    Route::get('checkout/{reservation}', [\App\Http\Controllers\CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('checkout/{reservation}/confirm', [\App\Http\Controllers\CheckoutController::class, 'confirm'])->name('checkout.confirm');
    Route::post('reservations/{reservation}/cancel', [\App\Http\Controllers\ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::get('reservations/{reservation}/tickets-pdf', [\App\Http\Controllers\ReservationController::class, 'downloadTicketsPdf'])->name('reservations.tickets-pdf');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    Route::resource('venues', \App\Http\Controllers\Admin\VenueController::class)->except(['show']);
    Route::get('events/{event}/seats', [\App\Http\Controllers\Admin\EventController::class, 'seats'])->name('events.seats');
    Route::resource('events', \App\Http\Controllers\Admin\EventController::class)->except(['show']);
    Route::get('reservations', [\App\Http\Controllers\Admin\ReservationController::class, 'index'])->name('reservations.index');
    Route::post('reservations/{reservation}/authorize', [\App\Http\Controllers\Admin\ReservationController::class, 'authorizeReservation'])->name('reservations.authorize');
    Route::post('reservations/{reservation}/reject', [\App\Http\Controllers\Admin\ReservationController::class, 'rejectReservation'])->name('reservations.reject');
    Route::get('reservations/{reservation}/tickets-pdf', [\App\Http\Controllers\Admin\ReservationController::class, 'ticketsPdf'])->name('reservations.tickets-pdf');
    Route::post('reservations/{reservation}/resend-tickets', [\App\Http\Controllers\Admin\ReservationController::class, 'resendTickets'])->name('reservations.resend-tickets');
    Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/toggle-role', [\App\Http\Controllers\Admin\UserController::class, 'toggleRole'])->name('users.toggle-role');
    Route::get('events/{event}/ticket-template', [\App\Http\Controllers\Admin\TicketTemplateController::class, 'edit'])->name('ticket-templates.edit');
    Route::put('events/{event}/ticket-template', [\App\Http\Controllers\Admin\TicketTemplateController::class, 'update'])->name('ticket-templates.update');
    Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/pdf/entradas', [\App\Http\Controllers\Admin\ReportController::class, 'downloadEntradasPdf'])->name('reports.pdf.entradas');
    Route::get('reports/pdf/clientes', [\App\Http\Controllers\Admin\ReportController::class, 'downloadClientesPdf'])->name('reports.pdf.clientes');
    Route::get('reports/pdf/ventas', [\App\Http\Controllers\Admin\ReportController::class, 'downloadVentasPdf'])->name('reports.pdf.ventas');
    Route::get('reports/pdf/clientes-por-evento', [\App\Http\Controllers\Admin\ReportController::class, 'downloadClientesPorEventoPdf'])->name('reports.pdf.clientes-por-evento');
    Route::get('reports/audit', [\App\Http\Controllers\Admin\ReportController::class, 'audit'])->name('reports.audit');
    Route::get('reports/pdf/audit', [\App\Http\Controllers\Admin\ReportController::class, 'downloadAuditPdf'])->name('reports.pdf.audit');
});
