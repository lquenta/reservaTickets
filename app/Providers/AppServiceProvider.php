<?php

namespace App\Providers;

use App\Mail\Transport\MailgunTransport;
use App\Mail\Transport\BrevoTransport;
use App\Mail\Transport\SendGridTransport;
use App\Mail\Transport\SmtpKitTransport;
use App\Models\Event;
use App\Models\Reservation;
use App\Services\MailConfigService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Schema::hasTable('settings')) {
            try {
                MailConfigService::applyToConfig();
            } catch (\Throwable $e) {
                // Ignorar si falla (ej. migraciones no ejecutadas en cola)
            }
        }

        Mail::extend('smtpkit', function (array $config) {
            return new SmtpKitTransport(
                apiKey: $config['api_key'] ?? config('services.smtpkit.api_key', ''),
                apiUrl: $config['api_url'] ?? config('services.smtpkit.api_url', 'https://smtpkit.com/api/v1/send-email'),
                verifySsl: (bool) ($config['verify_ssl'] ?? true)
            );
        });

        Mail::extend('sendgrid', function (array $config) {
            return new SendGridTransport(
                apiKey: $config['api_key'] ?? config('services.sendgrid.api_key', ''),
                verifySsl: (bool) ($config['verify_ssl'] ?? true)
            );
        });

        Mail::extend('mailgun', function (array $config) {
            return new MailgunTransport(
                apiKey: $config['api_key'] ?? config('services.mailgun.secret', ''),
                domain: $config['domain'] ?? config('services.mailgun.domain', ''),
                endpoint: $config['endpoint'] ?? config('services.mailgun.endpoint', 'https://api.mailgun.net')
            );
        });

        Mail::extend('brevo', function (array $config) {
            return new BrevoTransport(
                apiKey: $config['api_key'] ?? config('services.brevo.api_key', ''),
                apiUrl: $config['api_url'] ?? config('services.brevo.api_url', 'https://api.brevo.com/v3/smtp/email'),
                verifySsl: (bool) ($config['verify_ssl'] ?? true)
            );
        });

        View::composer('layouts.app', function ($view) {
            if (! auth()->check() || auth()->user()->isAdmin()) {
                return;
            }
            $hasReservationInProgress = Reservation::where('user_id', auth()->id())
                ->where('status', Reservation::STATUS_INICIADO)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();
            $view->with('hasReservationInProgress', $hasReservationInProgress);
        });

        View::composer('layouts.admin', function ($view) {
            if (! auth()->check() || ! auth()->user()->isAdmin()) {
                return;
            }
            $pendingCount = Reservation::where('status', Reservation::STATUS_PENDIENTE_PAGO)->count();
            $eventsLowStock = Event::query()
                ->where('is_active', true)
                ->whereNotNull('venue_id')
                ->where('starts_at', '>', now())
                ->with('venue')
                ->get()
                ->filter(function (Event $event) {
                    $venue = $event->getRelationValue('venue');
                    if (! $venue) {
                        return false;
                    }
                    $totalSeats = $venue->seats()->where('blocked', false)->count();
                    if ($totalSeats === 0) {
                        return false;
                    }
                    $available = $event->availableSeats()->count();
                    if ($available <= 0) {
                        return true;
                    }
                    return $available <= 20 || $available <= $totalSeats * 0.1;
                })
                ->values();
            $view->with('adminAlerts', [
                'pending_reservations_count' => $pendingCount,
                'events_low_stock' => $eventsLowStock,
            ]);
        });
    }
}
