<?php

declare(strict_types=1);

namespace App\Enums;

enum LoanStatus: string
{
    case Active = 'active';
    case Overdue = 'overdue';
    case Returned = 'returned';

    public function isOpen(): bool
    {
        return in_array($this, [self::Active, self::Overdue], true);
    }

    public function isRenewable(): bool
    {
        return $this === self::Active;
    }
}
