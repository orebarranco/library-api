<?php

declare(strict_types=1);

namespace App\Exceptions\Loan;

use RuntimeException;

final class ReservationNotApprovedException extends RuntimeException
{
    public function __construct(
        public readonly string $reservationId,
    ) {
        parent::__construct("Reservation '{$reservationId}' is not approved.");
    }
}
