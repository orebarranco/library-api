<?php

declare(strict_types=1);

use App\Exceptions\Reservation\ReservationNotCancellableException;

it('exposes the reservation id that is not cancellable', function (): void {
    $exception = new ReservationNotCancellableException('reservation-123');

    expect($exception->reservationId)->toBe('reservation-123');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
