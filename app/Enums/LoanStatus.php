<?php

declare(strict_types=1);

namespace App\Enums;

enum LoanStatus: string
{
    case Active = 'active';
    case Overdue = 'overdue';
    case Returned = 'returned';

    /**
     * Statuses of a loan whose copy is still in the borrower's hands.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Active, self::Overdue];
    }

    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }

    public function isRenewable(): bool
    {
        return $this === self::Active;
    }
}
