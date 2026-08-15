<?php

declare(strict_types=1);

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\ReturnReminderNotification;

beforeEach(function (): void {
    $this->book = Book::factory()->create(['title' => 'The Tombs of Atuan']);
    $this->copy = BookCopy::factory()->create([
        'book_id' => $this->book->id,
        'code' => 'ATUAN-001',
    ]);
    $this->loan = Loan::factory()->active()->create(['book_copy_id' => $this->copy->id]);
    $this->notification = new ReturnReminderNotification($this->loan, 2);
});

it('sends via mail and database channels', function (): void {
    expect($this->notification->via(new User()))->toBe(['mail', 'database']);
});

it('mail contains book title, copy code, due date and renewal instructions', function (): void {
    $mail = $this->notification->toMail(new User());
    $body = implode(' ', $mail->introLines);

    expect($mail->subject)->toContain('The Tombs of Atuan');
    expect($mail->greeting)->toContain('due in 2 day(s)');
    expect($body)->toContain('The Tombs of Atuan');
    expect($body)->toContain('ATUAN-001');
    expect($body)->toContain($this->loan->due_date->toDayDateTimeString());
    expect($body)->toContain('renew this loan from your account');
});

it('serializes the expected database payload', function (): void {
    $data = $this->notification->toDatabase(new User());

    expect($data)->toBe([
        'loan_id' => $this->loan->id,
        'book_copy_id' => $this->copy->id,
        'book_title' => 'The Tombs of Atuan',
        'copy_code' => 'ATUAN-001',
        'due_date' => $this->loan->due_date->toIso8601String(),
        'days' => 2,
        'message' => 'Your loan is due in 2 day(s). Renew it from your account if you need more time.',
    ]);
});
