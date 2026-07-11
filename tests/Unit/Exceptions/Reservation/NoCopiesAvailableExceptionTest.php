<?php

declare(strict_types=1);

use App\Exceptions\Reservation\NoCopiesAvailableException;

it('exposes the book id that has no available copies', function (): void {
    $exception = new NoCopiesAvailableException('book-123');

    expect($exception->bookId)->toBe('book-123');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
