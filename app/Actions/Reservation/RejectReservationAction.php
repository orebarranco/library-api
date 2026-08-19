<?php

declare(strict_types=1);

namespace App\Actions\Reservation;

use App\DTOs\Reservation\RejectReservationDTO;
use App\Enums\AuditAction;
use App\Enums\ReservationStatus;
use App\Exceptions\Reservation\ReservationNotPendingException;
use App\Models\Reservation;
use App\Traits\LogsActivity;

final readonly class RejectReservationAction
{
    use LogsActivity;

    public function execute(Reservation $reservation, RejectReservationDTO $dto): Reservation
    {
        if ($reservation->status !== ReservationStatus::Pending) {
            throw new ReservationNotPendingException($reservation->id);
        }

        $original = $reservation->getAttributes();

        $reservation->update([
            'status' => ReservationStatus::Rejected,
            'reason' => $dto->reason,
        ]);

        self::logChanges(AuditAction::ReservationRejected, $reservation, $original);

        return $reservation;
    }
}
