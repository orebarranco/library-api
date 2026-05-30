<?php

declare(strict_types=1);

namespace App\Exceptions\Catalog;

use RuntimeException;

final class DuplicateIsbnException extends RuntimeException
{
    public function __construct(
        public readonly string $isbn,
    ) {
        parent::__construct("A book with ISBN '{$isbn}' already exists.");
    }
}
