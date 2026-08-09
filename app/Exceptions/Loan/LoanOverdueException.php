<?php

declare(strict_types=1);

namespace App\Exceptions\Loan;

use RuntimeException;

final class LoanOverdueException extends RuntimeException
{
    public function __construct(
        public readonly string $loanId,
    ) {
        parent::__construct("Loan '{$loanId}' is overdue.");
    }
}
