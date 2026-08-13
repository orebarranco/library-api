<?php

declare(strict_types=1);

use App\Exceptions\Fine\FineAlreadyClosedException;
use App\Exceptions\Fine\PaymentExceedsBalanceException;
use App\Exceptions\Fine\WaiveLimitExceededException;

it('FineAlreadyClosedException carries the fine id', function (): void {
    $exception = new FineAlreadyClosedException('fine-123');

    expect($exception->fineId)->toBe('fine-123');
    expect($exception->getMessage())->toContain('fine-123');
});

it('PaymentExceedsBalanceException carries the fine id and balance', function (): void {
    $exception = new PaymentExceedsBalanceException('fine-123', 12.5);

    expect($exception->fineId)->toBe('fine-123');
    expect($exception->balance)->toBe(12.5);
    expect($exception->getMessage())->toContain('12.50');
});

it('WaiveLimitExceededException carries the amount and the role limit', function (): void {
    $exception = new WaiveLimitExceededException('fine-123', 45.0, 20.0);

    expect($exception->fineId)->toBe('fine-123');
    expect($exception->amount)->toBe(45.0);
    expect($exception->limit)->toBe(20.0);
    expect($exception->getMessage())->toContain('45.00')->toContain('20.00');
});
