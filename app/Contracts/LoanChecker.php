<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\User;

interface LoanChecker
{
    public function hasActiveLoans(Book $book): bool;

    public function hasActiveLoanForCopy(BookCopy $copy): bool;

    public function hasOverdueLoans(User $user): bool;
}
