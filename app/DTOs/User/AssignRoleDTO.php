<?php

declare(strict_types=1);

namespace App\DTOs\User;

use App\Enums\UserRole;

final readonly class AssignRoleDTO
{
    public function __construct(
        public UserRole $role,
    ) {}
}
