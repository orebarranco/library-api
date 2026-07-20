<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class NewReservationNotification extends Notification implements ShouldQueue
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
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'book_id' => $this->reservation->book_id,
            'user_id' => $this->reservation->user_id,
            'message' => 'A new reservation has been created and is awaiting review.',
        ];
    }
}
