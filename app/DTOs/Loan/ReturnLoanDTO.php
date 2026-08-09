<?php

declare(strict_types=1);

namespace App\DTOs\Loan;

use InvalidArgumentException;

final readonly class ReturnLoanDTO
{
    public function __construct(
        public bool $damaged = false,
        public ?float $damage_amount = null,
    ) {
        if ($damaged && $damage_amount === null) {
            throw new InvalidArgumentException('A damage amount is required when a copy is returned damaged.');
        }
    }
}
