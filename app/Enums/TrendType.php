<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Dimension a loan trend is broken down by. Bound implicitly on the trends
 * route, so an unknown value never reaches the controller.
 */
enum TrendType: string
{
    case Category = 'category';
    case Month = 'month';
}
