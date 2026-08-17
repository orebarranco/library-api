<?php

declare(strict_types=1);

namespace App\DTOs\Report;

use Carbon\CarbonInterface;

final readonly class WeeklyReportDTO
{
    public function __construct(
        public CarbonInterface $period_start,
        public CarbonInterface $period_end,
        public int $loans_count,
        public int $returns_count,
        public float $fines_generated,
        public float $fines_collected,
    ) {}
}
