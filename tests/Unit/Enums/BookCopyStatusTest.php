<?php

declare(strict_types=1);

use App\Enums\BookCopyStatus;

it('only available status is loanable', function (): void {
    expect(BookCopyStatus::Available->isLoanable())->toBeTrue();
});

it('loaned status is not loanable', function (): void {
    expect(BookCopyStatus::Loaned->isLoanable())->toBeFalse();
});

it('maintenance status is not loanable', function (): void {
    expect(BookCopyStatus::Maintenance->isLoanable())->toBeFalse();
});

it('lost status is not loanable', function (): void {
    expect(BookCopyStatus::Lost->isLoanable())->toBeFalse();
});
