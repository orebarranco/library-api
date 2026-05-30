<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Book;

interface LoanChecker
{
    public function hasActiveLoans(Book $book): bool;
}
