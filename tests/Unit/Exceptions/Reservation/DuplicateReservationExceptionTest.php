<?php

declare(strict_types=1);

use App\Exceptions\Reservation\DuplicateReservationException;

it('exposes the book id for the duplicate reservation', function (): void {
    $exception = new DuplicateReservationException('book-123');

    expect($exception->bookId)->toBe('book-123');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
