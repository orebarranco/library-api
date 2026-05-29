<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/auth/register';
    $this->validPayload = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ];
});
it('registers a new user successfully', function (): void {
    $response = $this->postJson($this->endpoint, $this->validPayload);

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.attributes.name', $this->validPayload['name'])
        ->assertJsonPath('data.attributes.email', $this->validPayload['email']);

    expect($response->json('meta.token'))->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('users', [
        'email' => $this->validPayload['email'],
    ]);
});
it('fails registration with invalid data', function (array $payload, string|array $fields): void {
    $fields = (array) $fields;

    $response = $this->postJson($this->endpoint, array_merge($this->validPayload, $payload))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');

    foreach ($fields as $field) {
        $response->assertJsonFragment(['source' => ['pointer' => '/data/attributes/'.$field]]);
    }
})->with([
    'missing name' => [['name' => ''], 'name'],
    'name too long' => [['name' => Str::random(256)], 'name'],
    'missing email' => [['email' => ''], 'email'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'email too long' => [['email' => Str::random(250).'@example.com'], 'email'],
    'missing password' => [['password' => ''], 'password'],
    'password too short' => [['password' => 'Short1!'], 'password'],
    'password confirmation mismatch' => [['password_confirmation' => 'different'], 'password'],
]);
it('fails registration if email is already taken', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson($this->endpoint, array_merge($this->validPayload, ['email' => 'taken@example.com']))
        ->assertUnprocessable()
        ->assertJsonFragment(['source' => ['pointer' => '/data/attributes/email']]);
});
it('does not include sensitive information in response', function (): void {
    $response = $this->postJson($this->endpoint, $this->validPayload);

    $response->assertStatus(Response::HTTP_CREATED);

    expect($response->json('data.attributes'))->not->toHaveKeys(['password', 'remember_token']);
});
