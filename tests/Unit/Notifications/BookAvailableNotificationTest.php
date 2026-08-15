<?php

declare(strict_types=1);

use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\BookAvailableNotification;

beforeEach(function (): void {
    $this->book = Book::factory()->create(['title' => 'The Dispossessed']);
    $this->reservation = Reservation::factory()->approved()->create(['book_id' => $this->book->id]);
    $this->notification = new BookAvailableNotification($this->reservation);
});

it('sends via mail and database channels', function (): void {
    expect($this->notification->via(new User()))->toBe(['mail', 'database']);
});

it('mail contains book title and pickup instructions', function (): void {
    $mail = $this->notification->toMail(new User());
    $body = implode(' ', $mail->introLines);

    expect($mail->subject)->toContain('The Dispossessed');
    expect($body)->toContain('The Dispossessed');
    expect($body)->toContain('circulation desk');
});

it('serializes the expected database payload', function (): void {
    $data = $this->notification->toDatabase(new User());

    expect($data)->toBe([
        'reservation_id' => $this->reservation->id,
        'book_id' => $this->book->id,
        'book_title' => 'The Dispossessed',
        'message' => 'A copy of your reserved book is available. Pick it up at the circulation desk.',
    ]);
});
