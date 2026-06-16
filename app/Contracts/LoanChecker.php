<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Book;
use App\Models\BookCopy;

interface LoanChecker
{
    public function hasActiveLoans(Book $book): bool;

    public function hasActiveLoanForCopy(BookCopy $copy): bool;
}
