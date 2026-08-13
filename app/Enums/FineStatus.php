<?php

declare(strict_types=1);

namespace App\Enums;

enum FineStatus: string
{
    case Pending = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Waived = 'waived';

    /**
     * Statuses whose remaining balance still counts against the borrower.
     *
     * @return list<self>
     */
    public static function outstanding(): array
    {
        return [self::Pending, self::PartiallyPaid];
    }

    /**
     * Closed fines are settled: they can no longer be paid or waived, and they
     * stop counting towards the borrower's outstanding balance.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Paid, self::Waived], true);
    }
}
