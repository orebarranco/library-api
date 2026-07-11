<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->user = User::factory()->create(['role' => UserRole::User]);
    $this->otherUser = User::factory()->create(['role' => UserRole::User]);
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->reservation = Reservation::factory()->create(['user_id' => $this->user->id]);
});

it('user can view their own reservation', function (): void {
    Sanctum::actingAs($this->user);

    $this->getJson("/api/v1/reservations/{$this->reservation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $this->reservation->id);
});

it('user cannot view another users reservation', function (): void {
    Sanctum::actingAs($this->otherUser);

    $this->getJson("/api/v1/reservations/{$this->reservation->id}")
        ->assertForbidden();
});

it('librarian can view any reservation', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson("/api/v1/reservations/{$this->reservation->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $this->reservation->id);
});

it('returns 404 for non-existent reservation', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson('/api/v1/reservations/non-existent-id')
        ->assertNotFound();
});

it('returns 401 for unauthenticated request', function (): void {
    $this->getJson("/api/v1/reservations/{$this->reservation->id}")
        ->assertUnauthorized();
});
