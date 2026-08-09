<?php

declare(strict_types=1);

namespace App\Enums;

enum FineType: string
{
    case LateReturn = 'late_return';
    case Damage = 'damage';
    case Loss = 'loss';
}
