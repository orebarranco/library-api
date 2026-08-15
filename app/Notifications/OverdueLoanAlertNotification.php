<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OverdueLoanAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Loan $loan,
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
        $this->loan->loadMissing(['user', 'bookCopy.book']);

        return new MailMessage()
            ->subject("Overdue loan alert: {$this->loan->bookCopy->book->title}")
            ->greeting('A loan has crossed the overdue alert threshold.')
            ->line("Member: {$this->loan->user->name}")
            ->line("Email: {$this->loan->user->email}")
            ->line("Book: {$this->loan->bookCopy->book->title}")
            ->line("Copy: {$this->loan->bookCopy->code}")
            ->line("Days overdue: {$this->loan->days_overdue}");
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->loan->loadMissing(['user', 'bookCopy.book']);

        return [
            'loan_id' => $this->loan->id,
            'user_id' => $this->loan->user_id,
            'member_name' => $this->loan->user->name,
            'member_email' => $this->loan->user->email,
            'book_title' => $this->loan->bookCopy->book->title,
            'copy_code' => $this->loan->bookCopy->code,
            'days_overdue' => $this->loan->days_overdue,
            'message' => "{$this->loan->user->name} has a loan {$this->loan->days_overdue} day(s) overdue.",
        ];
    }
}
