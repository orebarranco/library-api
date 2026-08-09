<?php

declare(strict_types=1);

use App\Actions\Reservation\CreateReservationAction;
use App\Contracts\FineChecker;
use App\Contracts\LoanChecker;
use App\DTOs\Reservation\CreateReservationDTO;
use App\Enums\ReservationStatus;
use App\Exceptions\Reservation\DuplicateReservationException;
use App\Exceptions\Reservation\NoCopiesAvailableException;
use App\Exceptions\Reservation\OverdueLoansException;
use App\Exceptions\Reservation\ReservationLimitExceededException;
use App\Exceptions\Reservation\UnpaidFinesException;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Reservation;
use App\Models\User;

use function Pest\Laravel\mock;

beforeEach(function (): void {
    $this->loanChecker = mock(LoanChecker::class);
    $this->fineChecker = mock(FineChecker::class);
    $this->action = new CreateReservationAction($this->loanChecker, $this->fineChecker);
});

it('throws NoCopiesAvailableException when no available copies', function (): void {
    $book = Book::factory()->create();
    BookCopy::factory()->loaned()->create(['book_id' => $book->id]);
    $user = User::factory()->create();

    $dto = new CreateReservationDTO(book_id: $book->id);

    expect(fn () => $this->action->execute($dto, $user))
        ->toThrow(NoCopiesAvailableException::class);
});

it('throws DuplicateReservationException when duplicate active reservation exists', function (): void {
    $book = Book::factory()->create();
    BookCopy::factory()->available()->create(['book_id' => $book->id]);
    $user = User::factory()->create();
    Reservation::factory()->pending()->create(['user_id' => $user->id, 'book_id' => $book->id]);

    $dto = new CreateReservationDTO(book_id: $book->id);

    expect(fn () => $this->action->execute($dto, $user))
        ->toThrow(DuplicateReservationException::class);
});

it('throws ReservationLimitExceededException when user has 3 active reservations', function (): void {
    $book = Book::factory()->create();
    BookCopy::factory()->available()->create(['book_id' => $book->id]);
    $user = User::factory()->create();
    Reservation::factory()->pending()->count(3)->create(['user_id' => $user->id]);

    $dto = new CreateReservationDTO(book_id: $book->id);

    expect(fn () => $this->action->execute($dto, $user))
        ->toThrow(ReservationLimitExceededException::class);
});

it('throws UnpaidFinesException when pending fines >= 50', function (): void {
    $book = Book::factory()->create();
    BookCopy::factory()->available()->create(['book_id' => $book->id]);
    $user = User::factory()->create();

    $this->fineChecker->shouldReceive('pendingFinesTotal')->andReturn(50.0);

    $dto = new CreateReservationDTO(book_id: $book->id);

    expect(fn () => $this->action->execute($dto, $user))
        ->toThrow(UnpaidFinesException::class);
});

it('throws OverdueLoansException when user has overdue loans', function (): void {
    $book = Book::factory()->create();
    BookCopy::factory()->available()->create(['book_id' => $book->id]);
    $user = User::factory()->create();

    $this->fineChecker->shouldReceive('pendingFinesTotal')->andReturn(0.0);
    $this->loanChecker->shouldReceive('hasOverdueLoans')->andReturn(true);

    $dto = new CreateReservationDTO(book_id: $book->id);

    expect(fn () => $this->action->execute($dto, $user))
        ->toThrow(OverdueLoansException::class);
});

it('creates reservation with correct data when all conditions pass', function (): void {
    $book = Book::factory()->create();
    BookCopy::factory()->available()->create(['book_id' => $book->id]);
    $user = User::factory()->create();

    $this->fineChecker->shouldReceive('pendingFinesTotal')->andReturn(0.0);
    $this->loanChecker->shouldReceive('hasOverdueLoans')->andReturn(false);

    $dto = new CreateReservationDTO(book_id: $book->id);

    $reservation = $this->action->execute($dto, $user);

    expect($reservation)->toBeInstanceOf(Reservation::class)
        ->and($reservation->exists)->toBeTrue()
        ->and($reservation->user_id)->toBe($user->id)
        ->and($reservation->book_id)->toBe($book->id)
        ->and($reservation->status)->toBe(ReservationStatus::Pending)
        ->and($reservation->reserved_at)->not->toBeNull();
});

it('respects rule precedence when rules 1 2 4 5 are all violated simultaneously', function (): void {
    // Book has no available copy (rule 1) AND user already has a duplicate reservation for it (rule 2)
    // AND unpaid fines (rule 4) AND overdue loans (rule 5) — only rule #1 must surface.
    $book = Book::factory()->create();
    BookCopy::factory()->loaned()->create(['book_id' => $book->id]);
    $user = User::factory()->create();
    Reservation::factory()->pending()->create(['user_id' => $user->id, 'book_id' => $book->id]);

    $this->fineChecker->shouldReceive('pendingFinesTotal')->andReturn(100.0);
    $this->loanChecker->shouldReceive('hasOverdueLoans')->andReturn(true);

    $dto = new CreateReservationDTO(book_id: $book->id);

    expect(fn () => $this->action->execute($dto, $user))
        ->toThrow(NoCopiesAvailableException::class);
});
