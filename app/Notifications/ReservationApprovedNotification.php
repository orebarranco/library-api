<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ReservationApprovedNotification extends Notification implements ShouldQueue
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
            ->subject("Reservation approved: {$this->reservation->book->title}")
            ->greeting('Your reservation is ready for pickup.')
            ->line("Book: {$this->reservation->book->title}")
            ->line("Pick your copy up before {$this->expiryDeadline()}.")
            ->line('Reservations that are not picked up before the deadline are released automatically.');
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
            'expires_at' => $this->reservation->expires_at?->toIso8601String(),
            'message' => 'Your reservation has been approved and the copy is waiting for you.',
        ];
    }

    /**
     * The pickup window closes 72 hours after approval, mirroring ApproveReservationAction.
     */
    private function expiryDeadline(): string
    {
        return $this->reservation->expires_at?->toDayDateTimeString()
            ?? 'the pickup deadline shown in your reservation';
    }
}
