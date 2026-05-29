<?php

declare(strict_types=1);

namespace App\DTOs\User;

use App\Enums\UserRole;

final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public UserRole $role,
    ) {}
}
