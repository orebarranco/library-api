<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ReturnReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Loan $loan,
        public readonly int $days,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->loan->loadMissing('bookCopy.book');

        return new MailMessage()
            ->subject("Return reminder: {$this->loan->bookCopy->book->title}")
            ->greeting("Your loan is due in {$this->days} day(s).")
            ->line("Book: {$this->loan->bookCopy->book->title}")
            ->line("Copy: {$this->loan->bookCopy->code}")
            ->line("Due date: {$this->loan->due_date->toDayDateTimeString()}")
            ->line('You can renew this loan from your account if no one else is waiting for the copy.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->loan->loadMissing('bookCopy.book');

        return [
            'loan_id' => $this->loan->id,
            'book_copy_id' => $this->loan->book_copy_id,
            'book_title' => $this->loan->bookCopy->book->title,
            'copy_code' => $this->loan->bookCopy->code,
            'due_date' => $this->loan->due_date->toIso8601String(),
            'days' => $this->days,
            'message' => "Your loan is due in {$this->days} day(s). Renew it from your account if you need more time.",
        ];
    }
}
