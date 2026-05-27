<?php

namespace App\DTOs;

use App\Models\User;

readonly class AdminSaleContext
{
    public function __construct(
        public User $soldBy,
        public string $saleType,
        public bool $skipPendingLimit = true,
        public int $expiryMinutes = 10,
        public ?string $initialStatus = null,
    ) {}
}
