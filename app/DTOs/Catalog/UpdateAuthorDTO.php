<?php

declare(strict_types=1);

namespace App\DTOs\Catalog;

final readonly class UpdateAuthorDTO
{
    public function __construct(
        public string $name,
        public ?string $biography,
        public ?string $birth_date,
    ) {}
}
