<?php

declare(strict_types=1);

use App\Actions\User\CreateUserAction;
use App\DTOs\User\CreateUserDTO;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

beforeEach(function (): void {
    $this->action = new CreateUserAction();
});

it('creates a user and returns a User instance', function (): void {
    $dto = new CreateUserDTO(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'password123',
        role: UserRole::User,
    );

    $result = $this->action->execute($dto);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->exists)->toBeTrue()
        ->and($result->name)->toBe('John Doe')
        ->and($result->email)->toBe('john@example.com')
        ->and($result->role)->toBe(UserRole::User);

    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
});

it('creates a user with the specified role', function (UserRole $role): void {
    $dto = new CreateUserDTO(
        name: 'Test User',
        email: fake()->unique()->safeEmail(),
        password: 'password123',
        role: $role,
    );

    $result = $this->action->execute($dto);

    expect($result->role)->toBe($role);
})->with([
    'user' => UserRole::User,
    'librarian' => UserRole::Librarian,
    'admin' => UserRole::Admin,
]);

it('creates a user with active status by default', function (): void {
    $dto = new CreateUserDTO(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'password123',
        role: UserRole::User,
    );

    $result = $this->action->execute($dto);

    expect($result->status)->toBe(UserStatus::Active);
});

it('hashes the password', function (): void {
    $dto = new CreateUserDTO(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'plaintext',
        role: UserRole::User,
    );

    $result = $this->action->execute($dto);

    expect($result->password)->not->toBe('plaintext')
        ->and(password_verify('plaintext', (string) $result->password))->toBeTrue();
});

it('persists user with a ULID id', function (): void {
    $dto = new CreateUserDTO(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'password123',
        role: UserRole::User,
    );

    $result = $this->action->execute($dto);

    expect($result->id)->toBeString()->not->toBeEmpty()
        ->and($result->created_at)->not->toBeNull();
});
