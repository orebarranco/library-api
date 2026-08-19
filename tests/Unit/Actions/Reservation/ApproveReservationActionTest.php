<?php

declare(strict_types=1);

use App\Actions\Reservation\ApproveReservationAction;
use App\Enums\ReservationStatus;
use App\Exceptions\Reservation\ReservationNotPendingException;
use App\Models\Reservation;
use App\Models\User;

// Every audited action records who performed it, so these unit tests act as a
// user the same way the HTTP routes behind them always do.
beforeEach(function (): void {
    test()->actingAs(User::factory()->create());

    $this->action = new ApproveReservationAction();
});

it('sets approved status with correct timestamps and approved_by', function (): void {
    $librarian = User::factory()->create();
    $reservation = Reservation::factory()->pending()->create();

    $result = $this->action->execute($reservation, $librarian);

    expect($result->status)->toBe(ReservationStatus::Approved)
        ->and($result->approved_at)->not->toBeNull()
        ->and($result->approved_by)->toBe($librarian->id)
        ->and($result->expires_at)->not->toBeNull()
        ->and($result->approved_at->diffInHours($result->expires_at))->toEqual(72);
});

it('throws ReservationNotPendingException when reservation is not pending', function (): void {
    $librarian = User::factory()->create();
    $reservation = Reservation::factory()->rejected()->create();

    expect(fn () => $this->action->execute($reservation, $librarian))
        ->toThrow(ReservationNotPendingException::class);
});
