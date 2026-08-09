<?php

declare(strict_types=1);

namespace App\DTOs\Loan;

final readonly class CreateLoanDTO
{
    public function __construct(
        public string $reservation_id,
    ) {}
}
