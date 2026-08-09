<?php

declare(strict_types=1);

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function isCancellableByUser(): bool
    {
        return in_array($this, [self::Pending, self::Approved], true);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Approved], true);
    }
}
