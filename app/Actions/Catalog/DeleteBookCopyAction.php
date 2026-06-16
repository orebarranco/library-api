<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Contracts\LoanChecker;
use App\Exceptions\Catalog\BookCopyHasActiveLoanException;
use App\Models\BookCopy;

final readonly class DeleteBookCopyAction
{
    public function __construct(
        private LoanChecker $loanChecker,
    ) {}

    public function execute(BookCopy $bookCopy): void
    {
        if ($this->loanChecker->hasActiveLoanForCopy($bookCopy)) {
            throw new BookCopyHasActiveLoanException($bookCopy->id);
        }

        $bookCopy->delete();
    }
}
