<?php

declare(strict_types=1);

namespace App\DTOs\Catalog;

use App\Enums\BookCopyStatus;

final readonly class ChangeBookCopyStatusDTO
{
    public function __construct(
        public BookCopyStatus $status,
    ) {}
}
