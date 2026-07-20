<?php

declare(strict_types=1);

namespace App\Exceptions\Reservation;

use RuntimeException;

final class ReservationNotCancellableException extends RuntimeException
{
    public function __construct(
        public readonly string $reservationId,
    ) {
        parent::__construct("Reservation '{$reservationId}' cannot be cancelled.");
    }
}
