<?php

declare(strict_types=1);

namespace App\Actions\Loan;

use App\DTOs\Loan\CreateLoanDTO;
use App\Enums\BookCopyStatus;
use App\Enums\LoanStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\Loan\ReservationNotApprovedException;
use App\Exceptions\Reservation\NoCopiesAvailableException;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

final readonly class CreateLoanAction
{
    private const int LOAN_PERIOD_DAYS = 14;

    public function execute(CreateLoanDTO $dto): Loan
    {
        $reservation = Reservation::query()->findOrFail($dto->reservation_id);

        if ($reservation->status !== ReservationStatus::Approved) {
            throw new ReservationNotApprovedException($reservation->id);
        }

        return DB::transaction(function () use ($reservation): Loan {
            $copy = $this->claimAvailableCopy($reservation->book_id);

            $loanedAt = now();

            $loan = Loan::query()->create([
                'user_id' => $reservation->user_id,
                'book_copy_id' => $copy->id,
                'reservation_id' => $reservation->id,
                'loaned_at' => $loanedAt,
                'due_date' => $loanedAt->clone()->addDays(self::LOAN_PERIOD_DAYS),
                'renewal_count' => 0,
                'status' => LoanStatus::Active,
            ]);

            $copy->update(['status' => BookCopyStatus::Loaned]);
            $reservation->update(['status' => ReservationStatus::Completed]);

            return $loan;
        });
    }

    private function claimAvailableCopy(string $bookId): BookCopy
    {
        $copy = BookCopy::query()
            ->where('book_id', $bookId)
            ->where('status', BookCopyStatus::Available)
            ->lockForUpdate()
            ->first();

        if (! $copy instanceof BookCopy) {
            throw new NoCopiesAvailableException($bookId);
        }

        return $copy;
    }
}
