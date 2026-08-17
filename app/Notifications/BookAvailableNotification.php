<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BookAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Reservation $reservation,
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
        $this->reservation->loadMissing('book');

        return new MailMessage()
            ->subject("A copy is available: {$this->reservation->book->title}")
            ->greeting('A copy of your reserved book is back on the shelf.')
            ->line("Book: {$this->reservation->book->title}")
            ->line('Come to the circulation desk and ask for your reserved copy to check it out.')
            ->line('Bring your library card so the librarian can register the loan.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->reservation->loadMissing('book');

        return [
            'reservation_id' => $this->reservation->id,
            'book_id' => $this->reservation->book_id,
            'book_title' => $this->reservation->book->title,
            'message' => 'A copy of your reserved book is available. Pick it up at the circulation desk.',
        ];
    }
}
