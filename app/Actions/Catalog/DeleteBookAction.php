<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Contracts\LoanChecker;
use App\Exceptions\Catalog\BookHasActiveLoansException;
use App\Models\Book;

final class DeleteBookAction
{
    public function __construct(
        private readonly LoanChecker $loanChecker,
    ) {}

    public function execute(Book $book): void
    {
        if ($this->loanChecker->hasActiveLoans($book)) {
            throw new BookHasActiveLoansException($book->id);
        }

        $book->delete();
    }
}
