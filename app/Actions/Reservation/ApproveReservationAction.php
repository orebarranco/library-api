<?php

declare(strict_types=1);

namespace App\Actions\Reservation;

use App\Enums\AuditAction;
use App\Enums\ReservationStatus;
use App\Exceptions\Reservation\ReservationNotPendingException;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationApprovedNotification;
use App\Traits\LogsActivity;

final readonly class ApproveReservationAction
{
    use LogsActivity;

    private const int PICKUP_WINDOW_HOURS = 72;

    public function execute(Reservation $reservation, User $actingUser): Reservation
    {
        if ($reservation->status !== ReservationStatus::Pending) {
            throw new ReservationNotPendingException($reservation->id);
        }

        $approvedAt = now();
        $original = $reservation->getAttributes();

        $reservation->update([
            'status' => ReservationStatus::Approved,
            'approved_at' => $approvedAt,
            'approved_by' => $actingUser->id,
            'expires_at' => $approvedAt->clone()->addHours(self::PICKUP_WINDOW_HOURS),
        ]);

        self::logChanges(AuditAction::ReservationApproved, $reservation, $original);

        // Strict mode forbids lazy loading, so the recipient is fetched explicitly.
        $reservation->loadMissing('user');
        $reservation->user->notify(new ReservationApprovedNotification($reservation));

        return $reservation;
    }
}
