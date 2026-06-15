<?php

declare(strict_types=1);

namespace App\Enums;

enum BookCopyStatus: string
{
    case Available = 'available';
    case Loaned = 'loaned';
    case Maintenance = 'maintenance';
    case Lost = 'lost';

    public function isLoanable(): bool
    {
        return $this === self::Available;
    }
}
