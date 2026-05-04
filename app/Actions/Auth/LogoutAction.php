<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

final class LogoutAction
{
    public function execute(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
