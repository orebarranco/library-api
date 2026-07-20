<?php

declare(strict_types=1);

namespace App\Exceptions\Reservation;

use RuntimeException;

final class DuplicateReservationException extends RuntimeException
{
    public function __construct(
        public readonly string $bookId,
    ) {
        parent::__construct("An active reservation for book '{$bookId}' already exists.");
    }
}
