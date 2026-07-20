<?php

declare(strict_types=1);

use App\Actions\Reservation\CancelReservationAction;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Exceptions\Reservation\ReservationNotCancellableException;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->action = new CancelReservationAction();
});

it('user cancellation blocked for non-cancellable statuses', function (): void {
    $owner = User::factory()->create(['role' => UserRole::User]);
    $reservation = Reservation::factory()->completed()->create(['user_id' => $owner->id]);

    expect(fn () => $this->action->execute($reservation, $owner))
        ->toThrow(ReservationNotCancellableException::class);
});

it('user can cancel own pending or approved reservation', function (): void {
    $owner = User::factory()->create(['role' => UserRole::User]);
    $reservation = Reservation::factory()->pending()->create(['user_id' => $owner->id]);

    $result = $this->action->execute($reservation, $owner);

    expect($result->status)->toBe(ReservationStatus::Cancelled);
});

it('user cannot cancel another users reservation', function (): void {
    $owner = User::factory()->create(['role' => UserRole::User]);
    $otherUser = User::factory()->create(['role' => UserRole::User]);
    $reservation = Reservation::factory()->pending()->create(['user_id' => $owner->id]);

    expect(fn () => $this->action->execute($reservation, $otherUser))
        ->toThrow(AuthorizationException::class);
});

it('librarian cancellation allowed for any status', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $reservation = Reservation::factory()->completed()->create();

    $result = $this->action->execute($reservation, $librarian);

    expect($result->status)->toBe(ReservationStatus::Cancelled);
});

it('admin cancellation allowed for any status identically to librarian', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $reservation = Reservation::factory()->completed()->create();

    $result = $this->action->execute($reservation, $admin);

    expect($result->status)->toBe(ReservationStatus::Cancelled);
});
