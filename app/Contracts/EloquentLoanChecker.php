<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;

final class EloquentLoanChecker implements LoanChecker
{
    /**
     * @var list<LoanStatus>
     */
    private const array OPEN_STATUSES = [LoanStatus::Active, LoanStatus::Overdue];

    public function hasActiveLoans(Book $book): bool
    {
        return Loan::query()
            ->whereIn('status', self::OPEN_STATUSES)
            ->whereHas('bookCopy', fn (Builder $query): Builder => $query->where('book_id', $book->id))
            ->exists();
    }

    public function hasActiveLoanForCopy(BookCopy $copy): bool
    {
        return Loan::query()
            ->where('book_copy_id', $copy->id)
            ->whereIn('status', self::OPEN_STATUSES)
            ->exists();
    }

    public function hasOverdueLoans(User $user): bool
    {
        return Loan::query()
            ->where('user_id', $user->id)
            ->whereIn('status', self::OPEN_STATUSES)
            ->where('due_date', '<', now())
            ->exists();
    }
}
