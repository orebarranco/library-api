<?php

declare(strict_types=1);
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/users';
    $this->user = User::factory()->create();
});
it('returns a paginated list of users', function (): void {
    User::factory()->count(3)->create();

    Sanctum::actingAs($this->user);

    $response = $this->getJson($this->endpoint);

    $response->assertSuccessful()
        ->assertJsonPath('meta.pagination.total', 4)
        ->assertJsonStructure([
            'data' => [['type', 'id', 'attributes']],
            'meta' => ['pagination' => ['total', 'per_page', 'current_page', 'last_page']],
        ]);
});
it('requires authentication', function (): void {
    $this->getJson($this->endpoint)
        ->assertUnauthorized()
        ->assertJsonPath('errors.0.code', 'UNAUTHENTICATED');
});
it('requires a verified email', function (): void {
    $unverifiedUser = User::factory()->unverified()->create();
    Sanctum::actingAs($unverifiedUser);

    $this->getJson($this->endpoint)
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'EMAIL_NOT_VERIFIED');
});
it('can filter users by name', function (): void {
    User::factory()->create(['name' => 'Alice Smith']);
    User::factory()->create(['name' => 'Bob Jones']);

    Sanctum::actingAs($this->user);

    $response = $this->getJson($this->endpoint.'?filter[name]=Alice');

    $response->assertSuccessful();

    $names = collect($response->json('data'))->pluck('attributes.name');
    expect($names)->each->toContain('Alice');
    expect($response->json('meta.pagination.total'))->toBe(1);
});
it('can filter users by email', function (): void {
    User::factory()->create(['email' => 'alice@example.com']);
    User::factory()->create(['email' => 'bob@example.com']);

    Sanctum::actingAs($this->user);

    $response = $this->getJson($this->endpoint.'?filter[email]=alice');

    $response->assertSuccessful();

    expect($response->json('meta.pagination.total'))->toBe(1);
});
it('can sort users by name ascending', function (): void {
    User::factory()->create(['name' => 'Zebra']);
    User::factory()->create(['name' => 'Apple']);

    Sanctum::actingAs($this->user);

    $response = $this->getJson($this->endpoint.'?sort=name');

    $response->assertSuccessful();

    $names = collect($response->json('data'))->pluck('attributes.name')->values()->all();
    expect($names)->toBe(collect($names)->sort()->values()->all());
});
it('can sort users by name descending', function (): void {
    User::factory()->create(['name' => 'Zebra']);
    User::factory()->create(['name' => 'Apple']);

    Sanctum::actingAs($this->user);

    $response = $this->getJson($this->endpoint.'?sort=-name');

    $response->assertSuccessful();

    $names = collect($response->json('data'))->pluck('attributes.name')->values()->all();
    expect($names)->toBe(collect($names)->sortDesc()->values()->all());
});
it('rejects disallowed sort fields', function (): void {
    Sanctum::actingAs($this->user);

    $this->getJson($this->endpoint.'?sort=password')
        ->assertBadRequest();
});
