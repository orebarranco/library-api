<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Fine;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;

/**
 * Endpoints that move stock or money, each paired with a librarian-or-admin
 * caller where the route demands one.
 *
 * @return array<string, array{string, UserRole}>
 */
dataset('critical endpoints', [
    'create reservation' => ['/api/v1/reservations', UserRole::User],
    'create loan' => ['/api/v1/loans', UserRole::Librarian],
]);

/**
 * The limiter sits ahead of validation and authorisation, so an empty payload
 * still consumes an attempt. That is what lets a single helper drive every
 * critical route without building its domain fixtures.
 */
function hammer(string $url, int $times): TestResponse
{
    $response = null;

    for ($attempt = 0; $attempt < $times; $attempt++) {
        $response = test()->postJson($url);
    }

    return $response;
}

it('critical endpoints allow up to 10 requests per minute per user', function (string $url, UserRole $role): void {
    Sanctum::actingAs(User::factory()->create(['role' => $role]));

    expect(hammer($url, 10)->status())->not->toBe(Response::HTTP_TOO_MANY_REQUESTS);
})->with('critical endpoints');

it('11th request returns 429 with Retry-After header', function (string $url, UserRole $role): void {
    Sanctum::actingAs(User::factory()->create(['role' => $role]));

    hammer($url, 10);

    $response = $this->postJson($url)->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);

    expect($response->headers->get('Retry-After'))->not->toBeNull();
})->with('critical endpoints');

it('rate limit is per user — different users are not affected by each other', function (): void {
    $exhausted = User::factory()->create();

    Sanctum::actingAs($exhausted);
    hammer('/api/v1/reservations', 10);
    $this->postJson('/api/v1/reservations')->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);

    Sanctum::actingAs(User::factory()->create());

    expect($this->postJson('/api/v1/reservations')->status())
        ->not->toBe(Response::HTTP_TOO_MANY_REQUESTS);
});

it('paying a fine is rate limited', function (): void {
    $borrower = User::factory()->create();
    $fine = Fine::factory()->for($borrower)->create();

    Sanctum::actingAs($borrower);

    hammer("/api/v1/fines/{$fine->id}/pay", 10);

    $this->postJson("/api/v1/fines/{$fine->id}/pay")
        ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
});

it('reading endpoints are not affected by the critical limiter', function (): void {
    Sanctum::actingAs(User::factory()->create());

    for ($attempt = 0; $attempt < 11; $attempt++) {
        $this->getJson('/api/v1/reservations')->assertOk();
    }
});
