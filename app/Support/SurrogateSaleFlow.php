<?php

namespace App\Support;

readonly class SurrogateSaleFlow
{
    public function __construct(
        public string $sessionClientKey,
        public string $sessionEventKey,
        public string $layout,
        public string $contentSection,
        public string $eventsIndexRoute,
        public string $checkoutSuccessRoute,
        public string $routePrefix,
    ) {}

    public static function admin(): self
    {
        return new self(
            sessionClientKey: 'admin_surrogate.client_user_id',
            sessionEventKey: 'admin_surrogate.event_id',
            layout: 'layouts.admin',
            contentSection: 'admin',
            eventsIndexRoute: 'admin.events.index',
            checkoutSuccessRoute: 'admin.reservations.index',
            routePrefix: 'admin.',
        );
    }

    public static function seller(): self
    {
        return new self(
            sessionClientKey: 'seller_surrogate.client_user_id',
            sessionEventKey: 'seller_surrogate.event_id',
            layout: 'layouts.app',
            contentSection: 'content',
            eventsIndexRoute: 'seller.events.index',
            checkoutSuccessRoute: 'seller.events.index',
            routePrefix: 'seller.',
        );
    }

    public function route(string $name, mixed ...$params): string
    {
        return route($this->routePrefix.$name, $params);
    }
}
