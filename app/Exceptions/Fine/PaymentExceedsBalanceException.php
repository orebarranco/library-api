<?php

declare(strict_types=1);

namespace App\Exceptions\Fine;

use RuntimeException;

final class PaymentExceedsBalanceException extends RuntimeException
{
    public function __construct(
        public readonly string $fineId,
        public readonly float $balance,
    ) {
        parent::__construct(
            sprintf("Payment exceeds the remaining balance of %.2f on fine '%s'.", $balance, $fineId),
        );
    }
}
