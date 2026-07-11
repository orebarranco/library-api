<?php

declare(strict_types=1);

namespace App\Exceptions\Reservation;

use RuntimeException;

final class NoCopiesAvailableException extends RuntimeException
{
    public function __construct(
        public readonly string $bookId,
    ) {
        parent::__construct("Book '{$bookId}' has no available copies.");
    }
}
