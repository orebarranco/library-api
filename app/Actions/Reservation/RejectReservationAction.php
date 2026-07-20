<?php

declare(strict_types=1);

namespace App\Actions\Reservation;

use App\DTOs\Reservation\RejectReservationDTO;
use App\Enums\ReservationStatus;
use App\Exceptions\Reservation\ReservationNotPendingException;
use App\Models\Reservation;

final readonly class RejectReservationAction
{
    public function execute(Reservation $reservation, RejectReservationDTO $dto): Reservation
    {
        if ($reservation->status !== ReservationStatus::Pending) {
            throw new ReservationNotPendingException($reservation->id);
        }

        $reservation->update([
            'status' => ReservationStatus::Rejected,
            'reason' => $dto->reason,
        ]);

        return $reservation;
    }
}
