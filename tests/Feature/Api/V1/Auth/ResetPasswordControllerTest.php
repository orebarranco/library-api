<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/auth/reset-password';
    $this->user = User::factory()->create();
    $this->token = Password::createToken($this->user);
});
it('resets the password with a valid token', function (): void {
    $newPassword = 'NewPassword123!';

    $this->postJson($this->endpoint, [
        'email' => $this->user->email,
        'token' => $this->token,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])->assertOk();

    expect(Hash::check($newPassword, $this->user->fresh()->password))->toBeTrue();
});
it('fails with an invalid token', function (): void {
    $this->postJson($this->endpoint, [
        'email' => $this->user->email,
        'token' => 'invalid-token',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('errors.0.code', 'INVALID_PASSWORD_RESET_TOKEN');
});
it('fails when email does not match the token', function (): void {
    $otherUser = User::factory()->create();

    $this->postJson($this->endpoint, [
        'email' => $otherUser->email,
        'token' => $this->token,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('errors.0.code', 'INVALID_PASSWORD_RESET_TOKEN');
});
it('fails with validation errors', function (array $payload, string $field): void {
    $this->postJson($this->endpoint, $payload)
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR')
        ->assertJsonFragment(['source' => ['pointer' => '/data/attributes/'.$field]]);
})->with([
    'missing email' => [['email' => '', 'token' => 'tok', 'password' => 'pass1234', 'password_confirmation' => 'pass1234'], 'email'],
    'invalid email' => [['email' => 'not-an-email', 'token' => 'tok', 'password' => 'pass1234', 'password_confirmation' => 'pass1234'], 'email'],
    'missing token' => [['email' => 'user@example.com', 'token' => '', 'password' => 'pass1234', 'password_confirmation' => 'pass1234'], 'token'],
    'missing password' => [['email' => 'user@example.com', 'token' => 'tok', 'password' => '', 'password_confirmation' => ''], 'password'],
    'password too short' => [['email' => 'user@example.com', 'token' => 'tok', 'password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'password mismatch' => [['email' => 'user@example.com', 'token' => 'tok', 'password' => 'password123', 'password_confirmation' => 'different123'], 'password'],
]);
