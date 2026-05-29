<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use RuntimeException;

final class AccountSuspendedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Account suspended.');
    }
}
