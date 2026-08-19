<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTOs\User\AssignRoleDTO;
use App\Enums\AuditAction;
use App\Models\User;
use App\Traits\LogsActivity;

final class AssignRoleAction
{
    use LogsActivity;

    public function execute(User $user, AssignRoleDTO $data): User
    {
        $original = $user->getAttributes();

        $user->update(['role' => $data->role]);

        self::logChanges(AuditAction::RoleAssigned, $user, $original);

        return $user;
    }
}
