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
    Route::get('hero-slides', [\App\Http\Controllers\Admin\HeroSlideController::class, 'index'])->name('hero-slides.index');
    Route::post('hero-slides', [\App\Http\Controllers\Admin\HeroSlideController::class, 'store'])->name('hero-slides.store');
    Route::post('hero-slides/video', [\App\Http\Controllers\Admin\HeroSlideController::class, 'storeVideo'])->name('hero-slides.store-video');
    Route::post('hero-slides/use-slider', [\App\Http\Controllers\Admin\HeroSlideController::class, 'useSlider'])->name('hero-slides.use-slider');
    Route::delete('hero-slides/{hero_slide}', [\App\Http\Controllers\Admin\HeroSlideController::class, 'destroy'])->name('hero-slides.destroy');
    Route::patch('hero-slides/reorder', [\App\Http\Controllers\Admin\HeroSlideController::class, 'reorder'])->name('hero-slides.reorder');
    Route::get('site-content/quienes-somos', [\App\Http\Controllers\Admin\SiteContentController::class, 'quienesSomos'])->name('site-content.quienes-somos');
    Route::put('site-content/quienes-somos', [\App\Http\Controllers\Admin\SiteContentController::class, 'updateQuienesSomos'])->name('site-content.update-quienes-somos');
    Route::get('team-members', [\App\Http\Controllers\Admin\TeamMemberController::class, 'index'])->name('team-members.index');
    Route::get('team-members/create', [\App\Http\Controllers\Admin\TeamMemberController::class, 'create'])->name('team-members.create');
    Route::get('team-members/bulk-create', [\App\Http\Controllers\Admin\TeamMemberController::class, 'bulkCreate'])->name('team-members.bulk-create');
    Route::post('team-members/bulk-store', [\App\Http\Controllers\Admin\TeamMemberController::class, 'bulkStore'])->name('team-members.bulk-store');
    Route::post('team-members', [\App\Http\Controllers\Admin\TeamMemberController::class, 'store'])->name('team-members.store');
    Route::get('team-members/{team_member}/edit', [\App\Http\Controllers\Admin\TeamMemberController::class, 'edit'])->name('team-members.edit');
    Route::put('team-members/{team_member}', [\App\Http\Controllers\Admin\TeamMemberController::class, 'update'])->name('team-members.update');
    Route::delete('team-members/{team_member}', [\App\Http\Controllers\Admin\TeamMemberController::class, 'destroy'])->name('team-members.destroy');
    Route::patch('team-members/reorder', [\App\Http\Controllers\Admin\TeamMemberController::class, 'reorder'])->name('team-members.reorder');
    Route::get('site-content/hero', [\App\Http\Controllers\Admin\SiteContentController::class, 'hero'])->name('site-content.hero');
    Route::put('site-content/hero', [\App\Http\Controllers\Admin\SiteContentController::class, 'updateHero'])->name('site-content.update-hero');
    Route::resource('venues', \App\Http\Controllers\Admin\VenueController::class)->except(['show']);
    Route::get('events/{event}/seats', [\App\Http\Controllers\Admin\EventController::class, 'seats'])->name('events.seats');
    Route::resource('events', \App\Http\Controllers\Admin\EventController::class)->except(['show']);
    Route::get('reservations', [\App\Http\Controllers\Admin\ReservationController::class, 'index'])->name('reservations.index');
    Route::post('reservations/{reservation}/authorize', [\App\Http\Controllers\Admin\ReservationController::class, 'authorizeReservation'])->name('reservations.authorize');
    Route::post('reservations/{reservation}/reject', [\App\Http\Controllers\Admin\ReservationController::class, 'rejectReservation'])->name('reservations.reject');
    Route::post('reservations/{reservation}/cancel', [\App\Http\Controllers\Admin\ReservationController::class, 'cancelReservation'])->name('reservations.cancel');
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
    Route::get('reports/pdf/nombres-por-evento', [\App\Http\Controllers\Admin\ReportController::class, 'downloadNombresPorEventoPdf'])->name('reports.pdf.nombres-por-evento');
    Route::get('reports/audit', [\App\Http\Controllers\Admin\ReportController::class, 'audit'])->name('reports.audit');
    Route::get('reports/pdf/audit', [\App\Http\Controllers\Admin\ReportController::class, 'downloadAuditPdf'])->name('reports.pdf.audit');
    Route::get('mail-settings', [\App\Http\Controllers\Admin\MailSettingsController::class, 'index'])->name('mail-settings.index');
    Route::put('mail-settings', [\App\Http\Controllers\Admin\MailSettingsController::class, 'update'])->name('mail-settings.update');
    Route::get('notification-settings', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'index'])->name('notification-settings.index');
    Route::put('notification-settings', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'update'])->name('notification-settings.update');
});
