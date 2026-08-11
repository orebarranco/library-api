<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

final class EloquentFineChecker implements FineChecker
{
    public function pendingFinesTotal(User $user): float
    {
        return $user->pending_fines_total;
    }
}
