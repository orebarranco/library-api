<?php

declare(strict_types=1);

use App\Enums\FineStatus;

it('exposes the four fine statuses', function (): void {
    expect(array_map(fn (FineStatus $status): string => $status->value, FineStatus::cases()))
        ->toBe(['pending', 'partially_paid', 'paid', 'waived']);
});

it('treats paid and waived as closed', function (FineStatus $status): void {
    expect($status->isClosed())->toBeTrue();
})->with([FineStatus::Paid, FineStatus::Waived]);

it('treats pending and partially paid as open', function (FineStatus $status): void {
    expect($status->isClosed())->toBeFalse();
})->with([FineStatus::Pending, FineStatus::PartiallyPaid]);

it('lists pending and partially paid as the outstanding statuses', function (): void {
    expect(FineStatus::outstanding())->toBe([FineStatus::Pending, FineStatus::PartiallyPaid]);
});
