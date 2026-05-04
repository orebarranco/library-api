<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

final readonly class ForgotPasswordDTO
{
    public function __construct(
        public string $email,
    ) {}
}
