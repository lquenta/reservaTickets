<?php

namespace App\DTOs;

use App\Models\User;

readonly class ClientResolution
{
    public function __construct(
        public User $user,
        public bool $wasCreated,
    ) {}
}
