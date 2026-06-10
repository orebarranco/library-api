<?php

declare(strict_types=1);

use App\Contracts\NullLoanChecker;
use App\Models\Book;

it('always returns false for has active loans', function (): void {
    $checker = new NullLoanChecker();
    $book = Book::factory()->create();

    expect($checker->hasActiveLoans($book))->toBeFalse();
});
