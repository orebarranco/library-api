<?php

declare(strict_types=1);

use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationApprovedNotification;

beforeEach(function (): void {
    $this->book = Book::factory()->create(['title' => 'The Left Hand of Darkness']);
    $this->reservation = Reservation::factory()->approved()->create(['book_id' => $this->book->id]);
    $this->notification = new ReservationApprovedNotification($this->reservation);
});

it('sends via mail and database channels', function (): void {
    expect($this->notification->via(new User()))->toBe(['mail', 'database']);
});

it('mail subject contains book title', function (): void {
    $mail = $this->notification->toMail(new User());

    expect($mail->subject)->toContain('The Left Hand of Darkness');
});

it('mail body contains book title and expiry deadline', function (): void {
    $mail = $this->notification->toMail(new User());
    $body = implode(' ', $mail->introLines);

    expect($body)->toContain('The Left Hand of Darkness');
    expect($body)->toContain($this->reservation->expires_at->toDayDateTimeString());
});

it('mail body falls back to a generic deadline when expires_at is missing', function (): void {
    $this->reservation->update(['expires_at' => null]);

    $mail = new ReservationApprovedNotification($this->reservation)->toMail(new User());

    expect(implode(' ', $mail->introLines))
        ->toContain('the pickup deadline shown in your reservation');
});

it('serializes the expected database payload', function (): void {
    $data = $this->notification->toDatabase(new User());

    expect($data)->toBe([
        'reservation_id' => $this->reservation->id,
        'book_id' => $this->book->id,
        'book_title' => 'The Left Hand of Darkness',
        'expires_at' => $this->reservation->expires_at->toIso8601String(),
        'message' => 'Your reservation has been approved and the copy is waiting for you.',
    ]);
});
