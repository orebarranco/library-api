<?php

declare(strict_types=1);

namespace App\Exceptions\Reservation;

use RuntimeException;

final class UnpaidFinesException extends RuntimeException
{
    public function __construct(
        public readonly string $userId,
    ) {
        parent::__construct("User '{$userId}' has unpaid fines that exceed the allowed threshold.");
    }
}
