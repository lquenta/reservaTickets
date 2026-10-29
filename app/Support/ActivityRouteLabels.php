<?php

namespace App\Support;

class ActivityRouteLabels
{
    /** @var array<string, string> */
    private const LABELS = [
        'home' => 'Inicio',
        'terms' => 'Términos y condiciones',
        'privacy' => 'Política de privacidad',
        'events.index' => 'Cartelera',
        'events.gallery' => 'Galería del evento',
        'login' => 'Ingreso',
        'register' => 'Registro',
        'password.request' => 'Olvidé mi contraseña',
        'password.reset' => 'Nueva contraseña',
        'reservations.entry' => 'Entrada a la reserva',
        'reservations.create' => 'Elegir asientos',
        'reservations.seats' => 'Mapa de asientos',
        'reservations.index' => 'Mis reservas',
        'checkout.show' => 'Pago',
        'seller.events.index' => 'Vendedor · eventos',
        'seller.events.seats' => 'Vendedor · asientos',
        'admin.dashboard' => 'Admin · inicio',
        'admin.reports.index' => 'Admin · reportes',
        'admin.reports.activity' => 'Admin · actividad',
        'admin.reports.audit' => 'Admin · auditoría',
        'admin.reports.metrics' => 'Admin · métricas',
        'admin.reservations.index' => 'Admin · reservas',
        'admin.users.index' => 'Admin · usuarios',
        'admin.events.index' => 'Admin · eventos',
        'admin.events.show' => 'Admin · evento',
        'admin.venues.index' => 'Admin · lugares',
    ];

    public static function page(?string $routeName, ?string $path): string
    {
        if ($routeName !== null && isset(self::LABELS[$routeName])) {
            return self::LABELS[$routeName];
        }

        return $path !== null && $path !== '' ? $path : '—';
    }
}
