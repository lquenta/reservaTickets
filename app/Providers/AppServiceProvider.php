<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Reservation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
