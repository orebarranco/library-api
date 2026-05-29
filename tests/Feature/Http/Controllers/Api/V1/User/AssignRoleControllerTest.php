<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/users';
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

it('admin can promote user to librarian', function (): void {
    $user = User::factory()->create(['role' => UserRole::User]);
    Sanctum::actingAs($this->admin);

    $this->putJson("{$this->endpoint}/{$user->id}/role", ['role' => 'librarian'])
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.role', 'librarian');

    expect($user->fresh()->role)->toBe(UserRole::Librarian);
});

it('admin can promote user to admin', function (): void {
    $user = User::factory()->create(['role' => UserRole::User]);
    Sanctum::actingAs($this->admin);

    $this->putJson("{$this->endpoint}/{$user->id}/role", ['role' => 'admin'])
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.role', 'admin');

    expect($user->fresh()->role)->toBe(UserRole::Admin);
});

it('admin can demote librarian to user', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($this->admin);

    $this->putJson("{$this->endpoint}/{$librarian->id}/role", ['role' => 'user'])
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.role', 'user');

    expect($librarian->fresh()->role)->toBe(UserRole::User);
});

it('returns 422 for invalid role value', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($this->admin);

    $this->putJson("{$this->endpoint}/{$user->id}/role", ['role' => 'superadmin'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('returns 403 for librarian role', function (): void {
    $user = User::factory()->create();
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $this->putJson("{$this->endpoint}/{$user->id}/role", ['role' => 'admin'])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
});

it('returns 403 for user role', function (): void {
    $target = User::factory()->create();
    $user = User::factory()->create(['role' => UserRole::User]);
    Sanctum::actingAs($user);

    $this->putJson("{$this->endpoint}/{$target->id}/role", ['role' => 'admin'])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
});
