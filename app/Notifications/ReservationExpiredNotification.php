<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ReservationExpiredNotification extends Notification implements ShouldQueue
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
            ->subject("Reservation expired: {$this->reservation->book->title}")
            ->greeting('Your reservation has expired.')
            ->line("Book: {$this->reservation->book->title}")
            ->line('The pickup window closed before the copy was collected, so the reservation was released.')
            ->line('You can reserve this book again from the catalog whenever a copy is available.');
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
            'message' => 'Your reservation expired. You can reserve this book again from the catalog.',
        ];
    }
}
