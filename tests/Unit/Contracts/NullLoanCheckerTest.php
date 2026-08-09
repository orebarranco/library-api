<?php

declare(strict_types=1);

use App\Contracts\NullLoanChecker;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\User;

it('always returns false for has active loans', function (): void {
    $checker = new NullLoanChecker();
    $book = Book::factory()->create();

    expect($checker->hasActiveLoans($book))->toBeFalse();
});

it('always returns false for has active loan for copy', function (): void {
    $checker = new NullLoanChecker();
    $copy = BookCopy::factory()->create();

    expect($checker->hasActiveLoanForCopy($copy))->toBeFalse();
});

it('always returns false for has overdue loans', function (): void {
    $checker = new NullLoanChecker();
    $user = User::factory()->create();

    expect($checker->hasOverdueLoans($user))->toBeFalse();
});
