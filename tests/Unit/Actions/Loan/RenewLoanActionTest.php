<?php

declare(strict_types=1);

use App\Actions\Loan\RenewLoanAction;
use App\Contracts\FineChecker;
use App\Contracts\NullFineChecker;
use App\Exceptions\Loan\BookHasReservationsException;
use App\Exceptions\Loan\LoanAlreadyReturnedException;
use App\Exceptions\Loan\LoanOverdueException;
use App\Exceptions\Loan\RenewalLimitReachedException;
use App\Exceptions\Loan\RenewalTooLateException;
use App\Exceptions\Reservation\UnpaidFinesException;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;

/**
 * A fine checker that always reports the given pending total.
 */
function fineCheckerReturning(float $total): FineChecker
{
    return new readonly class($total) implements FineChecker
    {
        public function __construct(private float $total) {}

        public function pendingFinesTotal(User $user): float
        {
            return $this->total;
        }
    };
}

beforeEach(function (): void {
    $this->action = new RenewLoanAction(new NullFineChecker());

    $this->member = User::factory()->create();
    $this->book = Book::factory()->create();
    $this->copy = BookCopy::factory()->create(['book_id' => $this->book->id]);
    $this->reservation = Reservation::factory()->completed()->create([
        'user_id' => $this->member->id,
        'book_id' => $this->book->id,
    ]);

    $this->loan = Loan::factory()->active()->create([
        'user_id' => $this->member->id,
        'book_copy_id' => $this->copy->id,
        'reservation_id' => $this->reservation->id,
    ]);
});

it('extends due_date by 14 days from today and increments renewal_count', function (): void {
    $result = $this->action->execute($this->loan);

    expect($result->renewal_count)->toBe(1);
    expect($result->due_date->isSameDay(now()->addDays(14)))->toBeTrue();
});

it('throws LoanAlreadyReturnedException when the loan is already returned', function (): void {
    $loan = Loan::factory()->returned()->create([
        'book_copy_id' => $this->copy->id,
        'reservation_id' => $this->reservation->id,
    ]);

    expect(fn () => $this->action->execute($loan))
        ->toThrow(LoanAlreadyReturnedException::class);
});

it('throws RenewalLimitReachedException when renewal_count is already 2', function (): void {
    $this->loan->update(['renewal_count' => 2]);

    expect(fn () => $this->action->execute($this->loan))
        ->toThrow(RenewalLimitReachedException::class);
});

it('throws LoanOverdueException when the due date has passed', function (): void {
    $this->loan->update(['due_date' => now()->subDay()]);

    expect(fn () => $this->action->execute($this->loan))
        ->toThrow(LoanOverdueException::class);
});

it('throws BookHasReservationsException when the book has a pending reservation', function (): void {
    Reservation::factory()->pending()->create(['book_id' => $this->book->id]);

    expect(fn () => $this->action->execute($this->loan))
        ->toThrow(BookHasReservationsException::class);
});

it('throws UnpaidFinesException when the user has any pending fine', function (): void {
    $action = new RenewLoanAction(fineCheckerReturning(0.5));

    expect(fn (): Loan => $action->execute($this->loan))
        ->toThrow(UnpaidFinesException::class);
});

it('allows renewal when the user has no pending fines', function (): void {
    $action = new RenewLoanAction(fineCheckerReturning(0.0));

    expect($action->execute($this->loan)->renewal_count)->toBe(1);
});

it('throws RenewalTooLateException when fewer than 2 days remain', function (): void {
    $this->loan->update(['due_date' => now()->addDay()]);

    expect(fn () => $this->action->execute($this->loan))
        ->toThrow(RenewalTooLateException::class);
});
