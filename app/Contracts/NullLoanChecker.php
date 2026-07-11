<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\User;

final class NullLoanChecker implements LoanChecker
{
    public function hasActiveLoans(Book $book): bool
    {
        return false;
    }

    public function hasActiveLoanForCopy(BookCopy $copy): bool
    {
        return false;
    }

    public function hasOverdueLoans(User $user): bool
    {
        return false;
    }
}
