<?php

declare(strict_types=1);

namespace App\Exceptions\Reservation;

use RuntimeException;

final class ReservationNotPendingException extends RuntimeException
{
    public function __construct(
        public readonly string $reservationId,
    ) {
        parent::__construct("Reservation '{$reservationId}' is not pending.");
    }
}
