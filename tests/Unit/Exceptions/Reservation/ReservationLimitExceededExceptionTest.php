<?php

declare(strict_types=1);

use App\Exceptions\Reservation\ReservationLimitExceededException;

it('exposes the user id that exceeded the reservation limit', function (): void {
    $exception = new ReservationLimitExceededException('user-123');

    expect($exception->userId)->toBe('user-123');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
