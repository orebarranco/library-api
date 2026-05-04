<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/auth/login';
    $this->password = 'Password123!';
    $this->user = User::factory()->create([
        'password' => Hash::make($this->password),
    ]);
});
it('logs in a user successfully with valid credentials', function (): void {
    $response = $this->postJson($this->endpoint, [
        'email' => $this->user->email,
        'password' => $this->password,
    ]);

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.attributes.email', $this->user->email);

    expect($response->json('meta.token'))->toBeString()->not->toBeEmpty();
});
it('fails login with invalid credentials', function (): void {
    $response = $this->postJson($this->endpoint, [
        'email' => $this->user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('errors.0.code', 'INVALID_CREDENTIALS');
});
it('fails login with non-existent email', function (): void {
    $response = $this->postJson($this->endpoint, [
        'email' => 'nonexistent@example.com',
        'password' => $this->password,
    ]);

    $response->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('errors.0.code', 'INVALID_CREDENTIALS');
});
it('fails login with validation errors', function (array $payload, string $field): void {
    $this->postJson($this->endpoint, $payload)
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR')
        ->assertJsonFragment(['source' => ['pointer' => '/data/attributes/'.$field]]);
})->with([
    'missing email' => [['email' => '', 'password' => 'password'], 'email'],
    'invalid email' => [['email' => 'not-an-email', 'password' => 'password'], 'email'],
    'missing password' => [['email' => 'test@example.com', 'password' => ''], 'password'],
]);
