<?php

declare(strict_types=1);

use App\Enums\LoanStatus;

test('has the expected cases and values', function (): void {
    expect(LoanStatus::Active->value)->toBe('active');
    expect(LoanStatus::Overdue->value)->toBe('overdue');
    expect(LoanStatus::Returned->value)->toBe('returned');
    expect(LoanStatus::cases())->toHaveCount(3);
});

test('isOpen is true for active and overdue only', function (): void {
    expect(LoanStatus::Active->isOpen())->toBeTrue();
    expect(LoanStatus::Overdue->isOpen())->toBeTrue();
    expect(LoanStatus::Returned->isOpen())->toBeFalse();
});

test('isRenewable is true for active only', function (): void {
    expect(LoanStatus::Active->isRenewable())->toBeTrue();
    expect(LoanStatus::Overdue->isRenewable())->toBeFalse();
    expect(LoanStatus::Returned->isRenewable())->toBeFalse();
});
