<?php

namespace App\DataTransferObjects;

use App\Models\User;
use Carbon\CarbonInterface;

final readonly class AuthResult
{
    public function __construct(
        public User $user,
        public string $token,
        public CarbonInterface $expiresAt,
    ) {
    }
}
