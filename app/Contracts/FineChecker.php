<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

interface FineChecker
{
    public function pendingFinesTotal(User $user): float;
}
