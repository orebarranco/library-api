<?php

declare(strict_types=1);
use App\Actions\Auth\ResetPasswordAction;
use App\DTOs\Auth\ResetPasswordDTO;
use App\Exceptions\Auth\InvalidPasswordResetTokenException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

beforeEach(function (): void {
    $this->action = new ResetPasswordAction();
});
it('resets the password and fires PasswordReset event', function (): void {
    Event::fake();

    $user = User::factory()->create();
    $token = Password::createToken($user);
    $newPassword = 'NewSecurePass123!';

    $this->action->execute(new ResetPasswordDTO(
        email: $user->email,
        token: $token,
        password: $newPassword,
    ));

    expect(Hash::check($newPassword, $user->fresh()->password))->toBeTrue();

    Event::assertDispatched(PasswordReset::class, fn (PasswordReset $e): bool => $e->user->is($user));
});
it('revokes all sanctum tokens after a successful reset', function (): void {
    $user = User::factory()->create();
    $user->createToken('device-1');
    $user->createToken('device-2');

    $token = Password::createToken($user);

    $this->action->execute(new ResetPasswordDTO(
        email: $user->email,
        token: $token,
        password: 'NewSecurePass123!',
    ));

    expect($user->tokens()->count())->toBe(0);
});
it('invalidates the token after a successful reset', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->action->execute(new ResetPasswordDTO(
        email: $user->email,
        token: $token,
        password: 'NewSecurePass123!',
    ));

    expect(Password::tokenExists($user, $token))->toBeFalse();
});
it('throws an exception for an invalid token', function (): void {
    $user = User::factory()->create();

    $this->action->execute(new ResetPasswordDTO(
        email: $user->email,
        token: 'invalid-token',
        password: 'NewSecurePass123!',
    ));
})->throws(InvalidPasswordResetTokenException::class);
it('throws an exception when email does not match the token', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = Password::createToken($user);

    $this->action->execute(new ResetPasswordDTO(
        email: $otherUser->email,
        token: $token,
        password: 'NewSecurePass123!',
    ));
})->throws(InvalidPasswordResetTokenException::class);
