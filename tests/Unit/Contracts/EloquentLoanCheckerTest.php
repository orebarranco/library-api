<?php

declare(strict_types=1);

use App\Contracts\EloquentLoanChecker;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;

beforeEach(function (): void {
    $this->checker = new EloquentLoanChecker();
    $this->book = Book::factory()->create();
    $this->copy = BookCopy::factory()->create(['book_id' => $this->book->id]);
});

it('reports no active loans for a book without loans', function (): void {
    expect($this->checker->hasActiveLoans($this->book))->toBeFalse();
});

it('reports active loans for a book with an open loan', function (): void {
    Loan::factory()->active()->create(['book_copy_id' => $this->copy->id]);

    expect($this->checker->hasActiveLoans($this->book))->toBeTrue();
});

it('ignores returned loans when checking a book', function (): void {
    Loan::factory()->returned()->create(['book_copy_id' => $this->copy->id]);

    expect($this->checker->hasActiveLoans($this->book))->toBeFalse();
});

it('reports an active loan for a specific copy', function (): void {
    expect($this->checker->hasActiveLoanForCopy($this->copy))->toBeFalse();

    Loan::factory()->overdue()->create(['book_copy_id' => $this->copy->id]);

    expect($this->checker->hasActiveLoanForCopy($this->copy))->toBeTrue();
});

it('ignores loans belonging to other copies', function (): void {
    $otherCopy = BookCopy::factory()->create(['book_id' => $this->book->id]);
    Loan::factory()->active()->create(['book_copy_id' => $otherCopy->id]);

    expect($this->checker->hasActiveLoanForCopy($this->copy))->toBeFalse();
});

it('reports overdue loans for a user past their due date', function (): void {
    $user = User::factory()->create();

    expect($this->checker->hasOverdueLoans($user))->toBeFalse();

    Loan::factory()->overdue()->create(['user_id' => $user->id]);

    expect($this->checker->hasOverdueLoans($user))->toBeTrue();
});

it('does not report overdue loans for a user with only current loans', function (): void {
    $user = User::factory()->create();
    Loan::factory()->active()->create(['user_id' => $user->id]);

    expect($this->checker->hasOverdueLoans($user))->toBeFalse();
});

it('does not report a returned loan as overdue', function (): void {
    $user = User::factory()->create();
    Loan::factory()->returned()->create(['user_id' => $user->id, 'due_date' => now()->subDays(10)]);

    expect($this->checker->hasOverdueLoans($user))->toBeFalse();
});
