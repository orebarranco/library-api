<?php

declare(strict_types=1);

namespace App\Actions\Loan;

use App\Contracts\FineChecker;
use App\Enums\ReservationStatus;
use App\Exceptions\Loan\BookHasReservationsException;
use App\Exceptions\Loan\LoanAlreadyReturnedException;
use App\Exceptions\Loan\LoanOverdueException;
use App\Exceptions\Loan\RenewalLimitReachedException;
use App\Exceptions\Loan\RenewalTooLateException;
use App\Exceptions\Reservation\UnpaidFinesException;
use App\Models\Loan;
use App\Models\Reservation;

final readonly class RenewLoanAction
{
    private const int LOAN_PERIOD_DAYS = 14;

    private const int MAX_RENEWALS = 2;

    private const int RENEWAL_CUTOFF_DAYS = 2;

    public function __construct(
        private FineChecker $fineChecker,
    ) {}

    public function execute(Loan $loan): Loan
    {
        // Strict mode forbids lazy loading, so the guards get their relations up front.
        $loan->loadMissing('bookCopy', 'user');

        $this->guardNotReturned($loan);
        $this->guardRenewalLimit($loan);
        $this->guardNotOverdue($loan);
        $this->guardBookHasNoReservations($loan);
        $this->guardNoPendingFines($loan);
        $this->guardWithinRenewalWindow($loan);

        $loan->update([
            'due_date' => now()->addDays(self::LOAN_PERIOD_DAYS),
            'renewal_count' => $loan->renewal_count + 1,
        ]);

        return $loan;
    }

    private function guardNotReturned(Loan $loan): void
    {
        if (! $loan->status->isOpen()) {
            throw new LoanAlreadyReturnedException($loan->id);
        }
    }

    private function guardRenewalLimit(Loan $loan): void
    {
        if ($loan->renewal_count >= self::MAX_RENEWALS) {
            throw new RenewalLimitReachedException($loan->id);
        }
    }

    private function guardNotOverdue(Loan $loan): void
    {
        if ($loan->isOverdue()) {
            throw new LoanOverdueException($loan->id);
        }
    }

    private function guardBookHasNoReservations(Loan $loan): void
    {
        $bookId = $loan->bookCopy->book_id;

        $hasReservations = Reservation::query()
            ->where('book_id', $bookId)
            ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Approved])
            ->exists();

        if ($hasReservations) {
            throw new BookHasReservationsException($bookId);
        }
    }

    private function guardNoPendingFines(Loan $loan): void
    {
        if ($this->fineChecker->pendingFinesTotal($loan->user) > 0.0) {
            throw new UnpaidFinesException($loan->user_id);
        }
    }

    private function guardWithinRenewalWindow(Loan $loan): void
    {
        if ($loan->due_date->lessThan(now()->addDays(self::RENEWAL_CUTOFF_DAYS))) {
            throw new RenewalTooLateException($loan->id);
        }
    }
}
