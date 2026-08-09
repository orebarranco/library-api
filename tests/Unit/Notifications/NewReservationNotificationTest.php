<?php

declare(strict_types=1);

use App\Models\Reservation;
use App\Models\User;
use App\Notifications\NewReservationNotification;

it('is sent only via the database channel', function (): void {
    $reservation = Reservation::factory()->create();
    $notification = new NewReservationNotification($reservation);

    expect($notification->via(new User()))->toBe(['database']);
});

it('serializes the expected database payload', function (): void {
    $reservation = Reservation::factory()->create();
    $notification = new NewReservationNotification($reservation);

    $data = $notification->toDatabase(new User());

    expect($data)->toBe([
        'reservation_id' => $reservation->id,
        'book_id' => $reservation->book_id,
        'user_id' => $reservation->user_id,
        'message' => 'A new reservation has been created and is awaiting review.',
    ]);
});
