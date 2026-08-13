<?php

declare(strict_types=1);

namespace App\Actions\Fine;

use App\Enums\UserStatus;
use App\Models\User;

/**
 * Keeps the account status in step with the borrower's outstanding fine balance:
 * reaching $100 suspends the account, and dropping back below it restores access.
 */
final class ReconcileAccountSuspensionAction
{
    private const float SUSPENSION_THRESHOLD = 100.0;

    public function execute(User $user): void
    {
        if ($user->pending_fines_total >= self::SUSPENSION_THRESHOLD) {
            if ($user->status === UserStatus::Active) {
                $user->update(['status' => UserStatus::Suspended]);
            }

            return;
        }

        if ($user->status === UserStatus::Suspended) {
            $user->update(['status' => UserStatus::Active]);
        }
    }
}
