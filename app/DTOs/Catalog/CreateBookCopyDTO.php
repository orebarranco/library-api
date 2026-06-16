<?php

declare(strict_types=1);

namespace App\DTOs\Catalog;

final readonly class CreateBookCopyDTO
{
    public function __construct(
        public ?string $acquisition_date,
    ) {}
}
