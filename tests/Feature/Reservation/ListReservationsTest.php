<?php

declare(strict_types=1);

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/reservations';
    $this->user = User::factory()->create(['role' => UserRole::User]);
    $this->otherUser = User::factory()->create(['role' => UserRole::User]);
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

it('user sees only their own reservations', function (): void {
    Sanctum::actingAs($this->user);

    Reservation::factory()->count(2)->create(['user_id' => $this->user->id]);
    Reservation::factory()->count(3)->create(['user_id' => $this->otherUser->id]);

    $this->getJson($this->endpoint)
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('librarian sees all reservations', function (): void {
    Sanctum::actingAs($this->librarian);

    Reservation::factory()->count(2)->create(['user_id' => $this->user->id]);
    Reservation::factory()->count(3)->create(['user_id' => $this->otherUser->id]);

    $this->getJson($this->endpoint)
        ->assertSuccessful()
        ->assertJsonCount(5, 'data');
});

it('admin sees all reservations', function (): void {
    Sanctum::actingAs($this->admin);

    Reservation::factory()->count(2)->create(['user_id' => $this->user->id]);
    Reservation::factory()->count(3)->create(['user_id' => $this->otherUser->id]);

    $this->getJson($this->endpoint)
        ->assertSuccessful()
        ->assertJsonCount(5, 'data');
});

it('returns paginated list default 15 per page', function (): void {
    Sanctum::actingAs($this->librarian);

    Reservation::factory()->count(20)->create();

    $this->getJson($this->endpoint)
        ->assertSuccessful()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.pagination.total', 20);
});

it('supports page parameter', function (): void {
    Sanctum::actingAs($this->librarian);

    Reservation::factory()->count(20)->create();

    $this->getJson($this->endpoint.'?page=2')
        ->assertSuccessful()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.pagination.current_page', 2);
});

it('supports filtering by status', function (): void {
    Sanctum::actingAs($this->librarian);

    Reservation::factory()->pending()->count(2)->create();
    Reservation::factory()->rejected()->count(3)->create();

    $this->getJson($this->endpoint.'?filter[status]=rejected')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.attributes.status', ReservationStatus::Rejected->value);
});

it('returns 401 for unauthenticated request', function (): void {
    $this->getJson($this->endpoint)
        ->assertUnauthorized();
});
