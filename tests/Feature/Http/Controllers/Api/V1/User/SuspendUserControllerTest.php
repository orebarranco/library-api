<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/users';
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

it('admin can suspend an active user', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    Sanctum::actingAs($this->admin);

    $this->postJson("{$this->endpoint}/{$user->id}/suspend")
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'suspended');

    expect($user->fresh()->status)->toBe(UserStatus::Suspended);
});

it('admin can unsuspend a suspended user', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Suspended]);
    Sanctum::actingAs($this->admin);

    $this->postJson("{$this->endpoint}/{$user->id}/unsuspend")
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'active');

    expect($user->fresh()->status)->toBe(UserStatus::Active);
});

it('suspended user cannot log in — returns 403 ACCOUNT_SUSPENDED', function (): void {
    User::factory()->create([
        'email' => 'suspended@example.com',
        'password' => bcrypt('password'),
        'status' => UserStatus::Suspended,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'suspended@example.com',
        'password' => 'password',
    ])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'ACCOUNT_SUSPENDED');
});

it('returns 403 for librarian role on suspend', function (): void {
    $user = User::factory()->create();
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $this->postJson("{$this->endpoint}/{$user->id}/suspend")
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
});

it('returns 403 for user role on suspend', function (): void {
    $target = User::factory()->create();
    $user = User::factory()->create(['role' => UserRole::User]);
    Sanctum::actingAs($user);

    $this->postJson("{$this->endpoint}/{$target->id}/suspend")
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
});
