<?php

declare(strict_types=1);
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/auth/logout';
    $this->user = User::factory()->create();
});
it('logs out an authenticated user successfully', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson($this->endpoint)
        ->assertNoContent();

    expect($this->user->tokens()->count())->toBe(0);
});
it('fails to logout if not authenticated', function (): void {
    $this->postJson($this->endpoint)
        ->assertUnauthorized()
        ->assertJsonPath('errors.0.code', 'UNAUTHENTICATED');
});
