<?php

declare(strict_types=1);

use App\Actions\Auth\LoginUserAction;
use App\DTOs\Auth\LoginUserDTO;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->action = new LoginUserAction();
});

it('can login a user with correct credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $dto = new LoginUserDTO(
        email: 'test@example.com',
        password: 'password123'
    );

    $result = $this->action->execute($dto);

    expect($result)->toBeArray()
        ->toHaveKey('user')
        ->toHaveKey('token')
        ->and($result['user'])->toBeInstanceOf(User::class)
        ->and($result['user']->id)->toBe($user->id)
        ->and($result['token'])->toBeString();
});

it('throws an exception with incorrect email', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $dto = new LoginUserDTO(
        email: 'wrong@example.com',
        password: 'password123'
    );

    $this->action->execute($dto);
})->throws(InvalidCredentialsException::class);

it('throws an exception with incorrect password', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $dto = new LoginUserDTO(
        email: 'test@example.com',
        password: 'wrong-password'
    );

    $this->action->execute($dto);
})->throws(InvalidCredentialsException::class);
