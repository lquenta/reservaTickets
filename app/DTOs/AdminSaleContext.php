<?php

namespace App\DTOs;

use App\Models\Reservation;
use App\Models\User;

readonly class AdminSaleContext
{
    public function __construct(
        public User $soldBy,
        public string $saleType,
        public bool $skipPendingLimit = true,
        public int $expiryMinutes = Reservation::HOLD_MINUTES,
        public ?string $initialStatus = null,
    ) {}
}
