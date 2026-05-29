<?php

declare(strict_types=1);

use App\Actions\User\UpdateUserAction;
use App\DTOs\User\UpdateUserDTO;
use App\Models\User;

beforeEach(function (): void {
    $this->action = new UpdateUserAction();
});

it('updates name and email and returns the updated user', function (): void {
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
    $dto = new UpdateUserDTO(name: 'New Name', email: 'new@example.com');

    $result = $this->action->execute($user, $dto);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->name)->toBe('New Name')
        ->and($result->email)->toBe('new@example.com');

    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'email' => 'new@example.com']);
});

it('returns the same user instance that was passed in', function (): void {
    $user = User::factory()->create();
    $dto = new UpdateUserDTO(name: 'Updated', email: 'updated@example.com');

    $result = $this->action->execute($user, $dto);

    expect($result->id)->toBe($user->id);
});

it('does not modify role or status', function (): void {
    $user = User::factory()->create();
    $originalRole = $user->role;
    $originalStatus = $user->status;
    $dto = new UpdateUserDTO(name: 'New Name', email: 'new@example.com');

    $this->action->execute($user, $dto);

    expect($user->fresh()->role)->toBe($originalRole)
        ->and($user->fresh()->status)->toBe($originalStatus);
});
