<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\OverdueLoanAlertNotification;

beforeEach(function (): void {
    $this->book = Book::factory()->create(['title' => 'The Farthest Shore']);
    $this->copy = BookCopy::factory()->create([
        'book_id' => $this->book->id,
        'code' => 'SHORE-007',
    ]);
    $this->member = User::factory()->create([
        'name' => 'Ged Sparrowhawk',
        'email' => 'ged@earthsea.test',
        'role' => UserRole::User,
    ]);
    $this->loan = Loan::factory()->overdue()->create([
        'user_id' => $this->member->id,
        'book_copy_id' => $this->copy->id,
    ]);
    $this->notification = new OverdueLoanAlertNotification($this->loan);
});

it('sends via mail and database channels', function (): void {
    expect($this->notification->via(new User()))->toBe(['mail', 'database']);
});

it('mail contains member name, email, book title and days overdue', function (): void {
    $mail = $this->notification->toMail(new User());
    $body = implode(' ', $mail->introLines);

    expect($mail->subject)->toContain('The Farthest Shore');
    expect($body)->toContain('Ged Sparrowhawk');
    expect($body)->toContain('ged@earthsea.test');
    expect($body)->toContain('The Farthest Shore');
    expect($body)->toContain('SHORE-007');
    expect($body)->toContain("Days overdue: {$this->loan->days_overdue}");
});

it('serializes the expected database payload', function (): void {
    $data = $this->notification->toDatabase(new User());

    expect($data)->toBe([
        'loan_id' => $this->loan->id,
        'user_id' => $this->member->id,
        'member_name' => 'Ged Sparrowhawk',
        'member_email' => 'ged@earthsea.test',
        'book_title' => 'The Farthest Shore',
        'copy_code' => 'SHORE-007',
        'days_overdue' => $this->loan->days_overdue,
        'message' => "Ged Sparrowhawk has a loan {$this->loan->days_overdue} day(s) overdue.",
    ]);
});
