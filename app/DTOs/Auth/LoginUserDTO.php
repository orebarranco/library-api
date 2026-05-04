<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

final readonly class LoginUserDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
