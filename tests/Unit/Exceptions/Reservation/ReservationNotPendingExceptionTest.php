<?php

declare(strict_types=1);

use App\Exceptions\Reservation\ReservationNotPendingException;

it('exposes the reservation id that is not pending', function (): void {
    $exception = new ReservationNotPendingException('reservation-123');

    expect($exception->reservationId)->toBe('reservation-123');
    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
