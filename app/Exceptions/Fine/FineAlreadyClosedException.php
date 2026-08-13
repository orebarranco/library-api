<?php

declare(strict_types=1);

namespace App\Exceptions\Fine;

use RuntimeException;

final class FineAlreadyClosedException extends RuntimeException
{
    public function __construct(
        public readonly string $fineId,
    ) {
        parent::__construct("Fine '{$fineId}' has already been paid or waived.");
    }
}
