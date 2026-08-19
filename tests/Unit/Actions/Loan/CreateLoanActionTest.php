<?php

declare(strict_types=1);

use App\Actions\Loan\CreateLoanAction;
use App\DTOs\Loan\CreateLoanDTO;
use App\Enums\BookCopyStatus;
use App\Enums\LoanStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\Loan\ReservationNotApprovedException;
use App\Exceptions\Reservation\NoCopiesAvailableException;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Reservation;
use App\Models\User;

// Every audited action records who performed it, so these unit tests act as a
// user the same way the HTTP routes behind them always do.
beforeEach(function (): void {
    test()->actingAs(User::factory()->create());

    $this->action = new CreateLoanAction();
    $this->member = User::factory()->create();
    $this->book = Book::factory()->create();
});

it('creates the loan with correct fields and updates copy and reservation status', function (): void {
    $copy = BookCopy::factory()->available()->create(['book_id' => $this->book->id]);
    $reservation = Reservation::factory()->approved()->create([
        'user_id' => $this->member->id,
        'book_id' => $this->book->id,
    ]);

    $loan = $this->action->execute(new CreateLoanDTO(reservation_id: $reservation->id));

    expect($loan->user_id)->toBe($this->member->id)
        ->and($loan->book_copy_id)->toBe($copy->id)
        ->and($loan->reservation_id)->toBe($reservation->id)
        ->and($loan->status)->toBe(LoanStatus::Active)
        ->and($loan->renewal_count)->toBe(0)
        ->and((int) $loan->loaned_at->diffInDays($loan->due_date, absolute: true))->toBe(14);

    expect($copy->refresh()->status)->toBe(BookCopyStatus::Loaned);
    expect($reservation->refresh()->status)->toBe(ReservationStatus::Completed);
});

it('throws ReservationNotApprovedException when the reservation is not approved', function (): void {
    BookCopy::factory()->available()->create(['book_id' => $this->book->id]);
    $reservation = Reservation::factory()->pending()->create(['book_id' => $this->book->id]);

    expect(fn () => $this->action->execute(new CreateLoanDTO(reservation_id: $reservation->id)))
        ->toThrow(ReservationNotApprovedException::class);
});

it('throws NoCopiesAvailableException when no available copies exist', function (): void {
    BookCopy::factory()->create(['book_id' => $this->book->id, 'status' => BookCopyStatus::Loaned]);
    $reservation = Reservation::factory()->approved()->create(['book_id' => $this->book->id]);

    expect(fn () => $this->action->execute(new CreateLoanDTO(reservation_id: $reservation->id)))
        ->toThrow(NoCopiesAvailableException::class);
});

it('picks an available copy and leaves the other copies untouched', function (): void {
    $loanedCopy = BookCopy::factory()->create(['book_id' => $this->book->id, 'status' => BookCopyStatus::Loaned]);
    $availableCopy = BookCopy::factory()->available()->create(['book_id' => $this->book->id]);
    $reservation = Reservation::factory()->approved()->create(['book_id' => $this->book->id]);

    $loan = $this->action->execute(new CreateLoanDTO(reservation_id: $reservation->id));

    expect($loan->book_copy_id)->toBe($availableCopy->id);
    expect($loanedCopy->refresh()->status)->toBe(BookCopyStatus::Loaned);
});
