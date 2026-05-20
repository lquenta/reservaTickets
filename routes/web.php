<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\AnalyticsEventController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('terminos-y-condiciones', function () {
    return view('legal.terms');
})->name('terms');

Route::get('events', [EventController::class, 'index'])->name('events.index');

Route::post('contact', [ContactController::class, 'store'])->name('contact.store');
Route::post('newsletter', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe')->middleware('throttle:5,1');
Route::post('analytics/events', [AnalyticsEventController::class, 'store'])->name('analytics.events.store')->middleware('throttle:120,1');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'verified', 'seller'])->name('seller.')->group(function () {
    Route::get('seller/events', [\App\Http\Controllers\Seller\EventController::class, 'index'])->name('events.index');
    Route::get('events/{event}/surrogate-sale', [\App\Http\Controllers\Seller\SurrogateSaleController::class, 'create'])->name('events.surrogate-sale.create');
    Route::post('events/{event}/surrogate-sale/lookup', [\App\Http\Controllers\Seller\SurrogateSaleController::class, 'lookup'])->name('events.surrogate-sale.lookup');
    Route::post('events/{event}/surrogate-sale/start', [\App\Http\Controllers\Seller\SurrogateSaleController::class, 'start'])->name('events.surrogate-sale.start');
    Route::get('events/{event}/surrogate-sale/seats', [\App\Http\Controllers\Seller\SurrogateSaleController::class, 'seats'])->name('events.surrogate-sale.seats');
    Route::post('events/{event}/surrogate-sale', [\App\Http\Controllers\Seller\SurrogateSaleController::class, 'store'])->name('events.surrogate-sale.store');
    Route::get('seller/surrogate-sale/checkout/{reservation}', [\App\Http\Controllers\Seller\SurrogateSaleController::class, 'checkout'])->name('surrogate-sale.checkout');
    Route::post('seller/surrogate-sale/checkout/{reservation}/confirm', [\App\Http\Controllers\Seller\SurrogateSaleController::class, 'confirm'])->name('surrogate-sale.checkout.confirm');
});

Route::middleware(['auth', 'verified', 'can.reserve'])->group(function () {
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
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
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
    Route::get('venues/{venue}/layout', [\App\Http\Controllers\Admin\VenueController::class, 'layout'])->name('venues.layout');
    Route::put('venues/{venue}/layout', [\App\Http\Controllers\Admin\VenueController::class, 'saveLayout'])->name('venues.layout.save');
    Route::get('events/{event}/seats', [\App\Http\Controllers\Admin\EventController::class, 'seats'])->name('events.seats');
    Route::post('events/{event}/seats/{seat}/block', [\App\Http\Controllers\Admin\EventController::class, 'blockSeat'])->name('events.seats.block');
    Route::delete('events/{event}/seats/{seat}/block', [\App\Http\Controllers\Admin\EventController::class, 'unblockSeat'])->name('events.seats.unblock');
    Route::patch('events/{event}/sold-out', [\App\Http\Controllers\Admin\EventController::class, 'markSoldOut'])->name('events.sold-out');
    Route::patch('events/{event}/pause-sales', [\App\Http\Controllers\Admin\EventController::class, 'pauseSales'])->name('events.pause-sales');
    Route::patch('events/{event}/resume-sales', [\App\Http\Controllers\Admin\EventController::class, 'resumePausedSales'])->name('events.resume-sales');
    Route::patch('events/{event}/reopen-sales', [\App\Http\Controllers\Admin\EventController::class, 'reopenSales'])->name('events.reopen-sales');
    Route::get('events/{event}/reschedule', [\App\Http\Controllers\Admin\EventRescheduleController::class, 'create'])->name('events.reschedule.create');
    Route::post('events/{event}/reschedule', [\App\Http\Controllers\Admin\EventRescheduleController::class, 'store'])->name('events.reschedule.store');
    Route::get('refunds', [\App\Http\Controllers\Admin\RefundController::class, 'index'])->name('refunds.index');
    Route::post('refunds/{reservation}/refund', [\App\Http\Controllers\Admin\RefundController::class, 'refund'])->name('refunds.refund');
    Route::resource('events', \App\Http\Controllers\Admin\EventController::class);
    Route::get('events/{event}/surrogate-sale', [\App\Http\Controllers\Admin\SurrogateSaleController::class, 'create'])->name('events.surrogate-sale.create');
    Route::post('events/{event}/surrogate-sale/lookup', [\App\Http\Controllers\Admin\SurrogateSaleController::class, 'lookup'])->name('events.surrogate-sale.lookup');
    Route::post('events/{event}/surrogate-sale/start', [\App\Http\Controllers\Admin\SurrogateSaleController::class, 'start'])->name('events.surrogate-sale.start');
    Route::get('events/{event}/surrogate-sale/seats', [\App\Http\Controllers\Admin\SurrogateSaleController::class, 'seats'])->name('events.surrogate-sale.seats');
    Route::post('events/{event}/surrogate-sale', [\App\Http\Controllers\Admin\SurrogateSaleController::class, 'store'])->name('events.surrogate-sale.store');
    Route::get('surrogate-sale/checkout/{reservation}', [\App\Http\Controllers\Admin\SurrogateSaleController::class, 'checkout'])->name('surrogate-sale.checkout');
    Route::post('surrogate-sale/checkout/{reservation}/confirm', [\App\Http\Controllers\Admin\SurrogateSaleController::class, 'confirm'])->name('surrogate-sale.checkout.confirm');
    Route::get('events/{event}/honored-guest', [\App\Http\Controllers\Admin\HonoredGuestController::class, 'create'])->name('events.honored-guest.create');
    Route::post('events/{event}/honored-guest/lookup', [\App\Http\Controllers\Admin\HonoredGuestController::class, 'lookup'])->name('events.honored-guest.lookup');
    Route::post('events/{event}/honored-guest/start', [\App\Http\Controllers\Admin\HonoredGuestController::class, 'start'])->name('events.honored-guest.start');
    Route::get('events/{event}/honored-guest/seats', [\App\Http\Controllers\Admin\HonoredGuestController::class, 'seats'])->name('events.honored-guest.seats');
    Route::post('events/{event}/honored-guest', [\App\Http\Controllers\Admin\HonoredGuestController::class, 'store'])->name('events.honored-guest.store');
    Route::get('reservations', [\App\Http\Controllers\Admin\ReservationController::class, 'index'])->name('reservations.index');
    Route::post('reservations/{reservation}/authorize', [\App\Http\Controllers\Admin\ReservationController::class, 'authorizeReservation'])->name('reservations.authorize');
    Route::post('reservations/{reservation}/reject', [\App\Http\Controllers\Admin\ReservationController::class, 'rejectReservation'])->name('reservations.reject');
    Route::post('reservations/{reservation}/cancel', [\App\Http\Controllers\Admin\ReservationController::class, 'cancelReservation'])->name('reservations.cancel');
    Route::get('reservations/{reservation}/tickets-pdf', [\App\Http\Controllers\Admin\ReservationController::class, 'ticketsPdf'])->name('reservations.tickets-pdf');
    Route::post('reservations/{reservation}/resend-tickets', [\App\Http\Controllers\Admin\ReservationController::class, 'resendTickets'])->name('reservations.resend-tickets');
    Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/set-role', [\App\Http\Controllers\Admin\UserController::class, 'setRole'])->name('users.set-role');
    Route::patch('users/{user}/verify-email', [\App\Http\Controllers\Admin\UserController::class, 'verifyEmail'])->name('users.verify-email');
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
    Route::get('reports/metrics', [\App\Http\Controllers\Admin\ReportController::class, 'metrics'])->name('reports.metrics');
    Route::get('reports/pdf/metrics', [\App\Http\Controllers\Admin\ReportController::class, 'downloadMetricsPdf'])->name('reports.pdf.metrics');
    Route::get('reports/pdf/reembolsos', [\App\Http\Controllers\Admin\ReportController::class, 'downloadRefundsPdf'])->name('reports.pdf.reembolsos');
    Route::get('mail-settings', [\App\Http\Controllers\Admin\MailSettingsController::class, 'index'])->name('mail-settings.index');
    Route::put('mail-settings', [\App\Http\Controllers\Admin\MailSettingsController::class, 'update'])->name('mail-settings.update');
    Route::post('mail-settings/test', [\App\Http\Controllers\Admin\MailSettingsController::class, 'sendTest'])->name('mail-settings.send-test');
    Route::get('notification-settings', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'index'])->name('notification-settings.index');
    Route::put('notification-settings', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'update'])->name('notification-settings.update');
});
