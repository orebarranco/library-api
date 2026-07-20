<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

final class NullFineChecker implements FineChecker
{
    public function pendingFinesTotal(User $user): float
    {
        return 0.0;
    }
}
