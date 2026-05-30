<?php

declare(strict_types=1);

use App\Actions\Catalog\DeleteBookAction;
use App\Contracts\LoanChecker;
use App\Exceptions\Catalog\BookHasActiveLoansException;
use App\Models\Book;

use function Pest\Laravel\mock;

it('soft-deletes book when no active loans', function (): void {
    $loanChecker = mock(LoanChecker::class);
    $loanChecker->shouldReceive('hasActiveLoans')->andReturn(false);

    $action = new DeleteBookAction($loanChecker);
    $book = Book::factory()->create();

    $action->execute($book);

    expect(Book::withTrashed()->find($book->id)->deleted_at)->not->toBeNull();
    expect(Book::query()->find($book->id))->toBeNull();
});

it('throws BookHasActiveLoansException when active loans exist', function (): void {
    $loanChecker = mock(LoanChecker::class);
    $loanChecker->shouldReceive('hasActiveLoans')->andReturn(true);

    $action = new DeleteBookAction($loanChecker);
    $book = Book::factory()->create();

    expect(fn () => $action->execute($book))
        ->toThrow(BookHasActiveLoansException::class);
});
