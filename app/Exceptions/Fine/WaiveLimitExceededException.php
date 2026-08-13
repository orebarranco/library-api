<?php

declare(strict_types=1);

namespace App\Exceptions\Fine;

use RuntimeException;

final class WaiveLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly string $fineId,
        public readonly float $amount,
        public readonly float $limit,
    ) {
        parent::__construct(
            sprintf(
                "Fine '%s' of %.2f exceeds the %.2f waive limit for this role.",
                $fineId,
                $amount,
                $limit,
            ),
        );
    }
}
