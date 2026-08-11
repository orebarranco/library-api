<?php

declare(strict_types=1);

namespace App\DTOs\Fine;

use InvalidArgumentException;

final readonly class PayFineDTO
{
    public function __construct(
        public float $amount,
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('A payment amount must be greater than zero.');
        }
    }
}
