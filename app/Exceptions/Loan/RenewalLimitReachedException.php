<?php

declare(strict_types=1);

namespace App\Exceptions\Loan;

use RuntimeException;

final class RenewalLimitReachedException extends RuntimeException
{
    public function __construct(
        public readonly string $loanId,
    ) {
        parent::__construct("Loan '{$loanId}' has reached the renewal limit.");
    }
}
