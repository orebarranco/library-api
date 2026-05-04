<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use RuntimeException;

final class InvalidPasswordResetTokenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This password reset token is invalid or has expired.');
    }
}
