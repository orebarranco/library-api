<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use RuntimeException;

final class EmailNotVerifiedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Your email address is not verified.');
    }
}
