<?php

declare(strict_types=1);

namespace App\Exceptions\Loan;

use RuntimeException;

final class RenewalTooLateException extends RuntimeException
{
    public function __construct(
        public readonly string $loanId,
    ) {
        parent::__construct("Loan '{$loanId}' is too close to its due date to be renewed.");
    }
}
