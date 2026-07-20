<?php

declare(strict_types=1);

use App\Exceptions\Reservation\OverdueLoansException;

it('exposes the user id with overdue loans', function (): void {
    $exception = new OverdueLoansException('user-123');

    expect($exception->userId)->toBe('user-123');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
