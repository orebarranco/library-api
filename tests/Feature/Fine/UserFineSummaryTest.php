<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Fine;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
    $this->endpoint = "/api/v1/users/{$this->member->id}/fines/summary";
});

it('librarian can view fine summary for any user', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->endpoint = "/api/v1/users/{$this->member->id}/fines/summary";

    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.type', 'fine-summaries')
        ->assertJsonPath('data.id', $this->member->id);
});

it('response includes total pending amount and count', function (): void {
    Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 30.0]);
    Fine::factory()->partiallyPaid()->create(['user_id' => $this->member->id, 'amount' => 20.0]);
    Fine::factory()->paid()->create(['user_id' => $this->member->id, 'amount' => 90.0]);
    Fine::factory()->waived()->create(['user_id' => $this->member->id, 'amount' => 75.0]);

    Sanctum::actingAs($this->librarian);

    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.attributes.pending_total', 40)
        ->assertJsonPath('data.attributes.pending_count', 2);
});

it('reports a zero summary for a user with no fines', function (): void {
    Sanctum::actingAs($this->admin);

    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.attributes.pending_total', 0)
        ->assertJsonPath('data.attributes.pending_count', 0);
});

it('returns 403 for user role', function (): void {
    Sanctum::actingAs($this->member);

    $this->getJson($this->endpoint)->assertForbidden();
});

it('returns 404 for a non-existent user', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson('/api/v1/users/01JQZZZZZZZZZZZZZZZZZZZZZZ/fines/summary')
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', 'NOT_FOUND');
});

it('returns 401 for unauthenticated request', function (): void {
    $this->getJson($this->endpoint)->assertUnauthorized();
});
