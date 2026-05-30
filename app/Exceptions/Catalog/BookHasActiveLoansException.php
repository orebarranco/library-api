<?php

declare(strict_types=1);

namespace App\Exceptions\Catalog;

use RuntimeException;

final class BookHasActiveLoansException extends RuntimeException
{
    public function __construct(
        public readonly string $bookId,
    ) {
        parent::__construct("Cannot delete book '{$bookId}' because it has active loans.");
    }
}
