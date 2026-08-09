<?php

declare(strict_types=1);

use App\Exceptions\Reservation\UnpaidFinesException;

it('exposes the user id with unpaid fines', function (): void {
    $exception = new UnpaidFinesException('user-123');

    expect($exception->userId)->toBe('user-123');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
