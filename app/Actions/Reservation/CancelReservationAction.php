<?php

declare(strict_types=1);

namespace App\Actions\Reservation;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Exceptions\Reservation\ReservationNotCancellableException;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class CancelReservationAction
{
    /**
     * @throws AuthorizationException
     */
    public function execute(Reservation $reservation, User $actingUser): Reservation
    {
        if ($actingUser->role === UserRole::User) {
            if ($reservation->user_id !== $actingUser->id) {
                throw new AuthorizationException();
            }

            if (! $reservation->status->isCancellableByUser()) {
                throw new ReservationNotCancellableException($reservation->id);
            }
        }

        $reservation->update(['status' => ReservationStatus::Cancelled]);

        return $reservation;
    }
}
