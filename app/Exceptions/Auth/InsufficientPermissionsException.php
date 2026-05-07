<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use Illuminate\Auth\Access\AuthorizationException;

final class InsufficientPermissionsException extends AuthorizationException
{
    public function __construct()
    {
        parent::__construct('Insufficient permissions.');
    }
}
