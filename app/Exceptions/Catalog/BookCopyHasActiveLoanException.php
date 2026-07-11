<?php

declare(strict_types=1);

namespace App\Exceptions\Catalog;

use RuntimeException;

final class BookCopyHasActiveLoanException extends RuntimeException
{
    public function __construct(
        public readonly string $copyId,
    ) {
        parent::__construct("Cannot delete book copy '{$copyId}' because it has an active loan.");
    }
}
