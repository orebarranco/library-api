<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\UserStatus;
use App\Models\User;

final class SuspendUserAction
{
    public function execute(User $user): void
    {
        $user->update(['status' => UserStatus::Suspended]);
    }
}
