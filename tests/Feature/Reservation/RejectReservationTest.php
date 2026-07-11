<?php

declare(strict_types=1);

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->user = User::factory()->create(['role' => UserRole::User]);
    $this->reservation = Reservation::factory()->pending()->create();
});

it('librarian can reject a pending reservation with a reason', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson("/api/v1/reservations/{$this->reservation->id}/reject", ['reason' => 'Out of stock long term.'])
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', ReservationStatus::Rejected->value);

    $this->assertDatabaseHas('reservations', [
        'id' => $this->reservation->id,
        'status' => ReservationStatus::Rejected->value,
        'reason' => 'Out of stock long term.',
    ]);
});

it('returns 422 if reason is missing', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson("/api/v1/reservations/{$this->reservation->id}/reject", [])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('returns 422 RESERVATION_NOT_PENDING if reservation is not pending', function (): void {
    Sanctum::actingAs($this->librarian);

    $approved = Reservation::factory()->approved()->create();

    $this->postJson("/api/v1/reservations/{$approved->id}/reject", ['reason' => 'Too late.'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'RESERVATION_NOT_PENDING');
});

it('returns 403 for user role', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson("/api/v1/reservations/{$this->reservation->id}/reject", ['reason' => 'Nope.'])
        ->assertForbidden();
});
