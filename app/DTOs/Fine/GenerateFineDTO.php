<?php

declare(strict_types=1);

namespace App\DTOs\Fine;

use App\Enums\FineType;

final readonly class GenerateFineDTO
{
    public function __construct(
        public string $user_id,
        public FineType $type,
        public float $amount,
        public string $description,
        public ?string $loan_id = null,
    ) {}
}
