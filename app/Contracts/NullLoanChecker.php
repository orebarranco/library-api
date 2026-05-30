<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Book;

final class NullLoanChecker implements LoanChecker
{
    public function hasActiveLoans(Book $book): bool
    {
        return false;
    }
}
