<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Rolling time windows a report can be narrowed to. Absence of a period means
 * the report covers the whole history, so there is no `all` case here.
 */
enum ReportPeriod: string
{
    case SevenDays = '7days';
    case ThirtyDays = '30days';
    case NinetyDays = '90days';
    case OneYear = '1year';

    /**
     * Start of the window, measured backwards from the present moment.
     */
    public function since(): CarbonInterface
    {
        return match ($this) {
            self::SevenDays => now()->subDays(7),
            self::ThirtyDays => now()->subDays(30),
            self::NinetyDays => now()->subDays(90),
            self::OneYear => now()->subYear(),
        };
    }
}
