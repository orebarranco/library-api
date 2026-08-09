<?php

declare(strict_types=1);

namespace App\Exceptions\Loan;

use RuntimeException;

final class BookHasReservationsException extends RuntimeException
{
    public function __construct(
        public readonly string $bookId,
    ) {
        parent::__construct("Book '{$bookId}' has active reservations.");
    }
}
