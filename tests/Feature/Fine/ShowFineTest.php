<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Fine;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
    $this->other = User::factory()->create(['role' => UserRole::User]);
    $this->fine = Fine::factory()->create(['user_id' => $this->member->id]);
    $this->endpoint = "/api/v1/fines/{$this->fine->id}";
});

it('user can view their own fine', function (): void {
    Sanctum::actingAs($this->member);

    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.type', 'fines')
        ->assertJsonPath('data.id', $this->fine->id);
});

it('user cannot view another users fine and receives 403', function (): void {
    Sanctum::actingAs($this->other);

    $this->getJson($this->endpoint)
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'UNAUTHORIZED');
});

it('librarian can view any fine', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.id', $this->fine->id);
});

it('returns 404 for a non-existent fine', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson('/api/v1/fines/01JQZZZZZZZZZZZZZZZZZZZZZZ')
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', 'NOT_FOUND');
});

it('returns 401 for unauthenticated request', function (): void {
    $this->getJson($this->endpoint)->assertUnauthorized();
});
