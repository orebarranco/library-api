<?php

declare(strict_types=1);

namespace App\Exceptions\Reservation;

use RuntimeException;

final class OverdueLoansException extends RuntimeException
{
    public function __construct(
        public readonly string $userId,
    ) {
        parent::__construct("User '{$userId}' has overdue loans.");
    }
}
