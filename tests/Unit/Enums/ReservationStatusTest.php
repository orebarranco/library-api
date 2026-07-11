<?php

declare(strict_types=1);

use App\Enums\ReservationStatus;

it('pending and approved are cancellable by user', function (): void {
    expect(ReservationStatus::Pending->isCancellableByUser())->toBeTrue();
    expect(ReservationStatus::Approved->isCancellableByUser())->toBeTrue();
});

it('completed rejected cancelled expired are not cancellable by user', function (): void {
    expect(ReservationStatus::Completed->isCancellableByUser())->toBeFalse();
    expect(ReservationStatus::Rejected->isCancellableByUser())->toBeFalse();
    expect(ReservationStatus::Cancelled->isCancellableByUser())->toBeFalse();
    expect(ReservationStatus::Expired->isCancellableByUser())->toBeFalse();
});

it('pending and approved are active statuses', function (): void {
    expect(ReservationStatus::Pending->isActive())->toBeTrue();
    expect(ReservationStatus::Approved->isActive())->toBeTrue();
});

it('completed rejected cancelled expired are not active statuses', function (): void {
    expect(ReservationStatus::Completed->isActive())->toBeFalse();
    expect(ReservationStatus::Rejected->isActive())->toBeFalse();
    expect(ReservationStatus::Cancelled->isActive())->toBeFalse();
    expect(ReservationStatus::Expired->isActive())->toBeFalse();
});
