<?php

declare(strict_types=1);

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create(['role' => UserRole::User]);
    $this->otherUser = User::factory()->create(['role' => UserRole::User]);
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

it('user can cancel their own pending reservation', function (): void {
    Sanctum::actingAs($this->user);

    $reservation = Reservation::factory()->pending()->create(['user_id' => $this->user->id]);

    $this->deleteJson("/api/v1/reservations/{$reservation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', ReservationStatus::Cancelled->value);
});

it('user can cancel their own approved reservation', function (): void {
    Sanctum::actingAs($this->user);

    $reservation = Reservation::factory()->approved()->create(['user_id' => $this->user->id]);

    $this->deleteJson("/api/v1/reservations/{$reservation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', ReservationStatus::Cancelled->value);
});

it('user cannot cancel a completed reservation', function (): void {
    Sanctum::actingAs($this->user);

    $reservation = Reservation::factory()->completed()->create(['user_id' => $this->user->id]);

    $this->deleteJson("/api/v1/reservations/{$reservation->id}")
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'RESERVATION_NOT_CANCELLABLE');
});

it('user cannot cancel another users reservation', function (): void {
    Sanctum::actingAs($this->otherUser);

    $reservation = Reservation::factory()->pending()->create(['user_id' => $this->user->id]);

    $this->deleteJson("/api/v1/reservations/{$reservation->id}")
        ->assertForbidden();
});

it('librarian can cancel any reservation regardless of status', function (): void {
    Sanctum::actingAs($this->librarian);

    $reservation = Reservation::factory()->completed()->create(['user_id' => $this->user->id]);

    $this->deleteJson("/api/v1/reservations/{$reservation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', ReservationStatus::Cancelled->value);
});

it('admin can cancel any reservation regardless of status', function (): void {
    Sanctum::actingAs($this->admin);

    $reservation = Reservation::factory()->completed()->create(['user_id' => $this->user->id]);

    $this->deleteJson("/api/v1/reservations/{$reservation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', ReservationStatus::Cancelled->value);
});

it('returns 404 for non-existent reservation', function (): void {
    Sanctum::actingAs($this->user);

    $this->deleteJson('/api/v1/reservations/non-existent-id')
        ->assertNotFound();
});
