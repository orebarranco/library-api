<?php

declare(strict_types=1);

use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationExpiredNotification;

beforeEach(function (): void {
    $this->book = Book::factory()->create(['title' => 'A Wizard of Earthsea']);
    $this->reservation = Reservation::factory()->expired()->create(['book_id' => $this->book->id]);
    $this->notification = new ReservationExpiredNotification($this->reservation);
});

it('sends via mail and database channels', function (): void {
    expect($this->notification->via(new User()))->toBe(['mail', 'database']);
});

it('mail contains book title and re-reserve instructions', function (): void {
    $mail = $this->notification->toMail(new User());
    $body = implode(' ', $mail->introLines);

    expect($mail->subject)->toContain('A Wizard of Earthsea');
    expect($body)->toContain('A Wizard of Earthsea');
    expect($body)->toContain('reserve this book again from the catalog');
});

it('serializes the expected database payload', function (): void {
    $data = $this->notification->toDatabase(new User());

    expect($data)->toBe([
        'reservation_id' => $this->reservation->id,
        'book_id' => $this->book->id,
        'book_title' => 'A Wizard of Earthsea',
        'message' => 'Your reservation expired. You can reserve this book again from the catalog.',
    ]);
});
