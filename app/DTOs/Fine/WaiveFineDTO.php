<?php

declare(strict_types=1);

namespace App\DTOs\Fine;

use InvalidArgumentException;

final readonly class WaiveFineDTO
{
    public function __construct(
        public string $reason,
    ) {
        if (mb_trim($reason) === '') {
            throw new InvalidArgumentException('A waive reason is required.');
        }
    }
}
