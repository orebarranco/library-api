<?php

declare(strict_types=1);

namespace App\DTOs\Catalog;

final readonly class CreateCategoryDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}
}
