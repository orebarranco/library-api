<?php

declare(strict_types=1);

namespace App\DTOs\User;

final readonly class UpdateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}
