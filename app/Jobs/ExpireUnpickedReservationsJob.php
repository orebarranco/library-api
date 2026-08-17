<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Notifications\ReservationExpiredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ExpireUnpickedReservationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $unpicked = Reservation::query()
            ->with(['user', 'book'])
            ->where('status', ReservationStatus::Approved)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($unpicked as $reservation) {
            $reservation->update(['status' => ReservationStatus::Expired]);

            $reservation->user->notify(new ReservationExpiredNotification($reservation));
        }
    }
}
