<?php

declare(strict_types=1);

namespace App\Exceptions\Reservation;

use RuntimeException;

final class ReservationLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly string $userId,
    ) {
        parent::__construct("User '{$userId}' already has the maximum number of active reservations.");
    }
}
