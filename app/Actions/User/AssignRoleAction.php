<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTOs\User\AssignRoleDTO;
use App\Models\User;

final class AssignRoleAction
{
    public function execute(User $user, AssignRoleDTO $data): User
    {
        $user->update(['role' => $data->role]);

        return $user;
    }
}
