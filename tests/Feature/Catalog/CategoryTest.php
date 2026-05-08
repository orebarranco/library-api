<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/categories';
});

it('anyone can list categories without authentication', function (): void {
    Category::factory()->count(3)->create();

    $this->getJson($this->endpoint)
        ->assertSuccessful();
});

it('returns JSON:API format', function (): void {
    Category::factory()->create(['name' => 'Fiction', 'description' => 'Fiction books']);

    $this->getJson($this->endpoint)
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [['type', 'id', 'attributes']],
            'meta',
        ])
        ->assertJsonPath('data.0.type', 'categories')
        ->assertJsonPath('data.0.attributes.name', 'Fiction')
        ->assertJsonPath('data.0.attributes.description', 'Fiction books');
});

it('supports ?page= parameter', function (): void {
    Category::factory()->count(20)->create();

    $this->getJson($this->endpoint.'?page=2')
        ->assertSuccessful()
        ->assertJsonPath('meta.pagination.current_page', 2);
});

it('returns paginated categories list', function (): void {
    Category::factory()->count(20)->create();

    $this->getJson($this->endpoint)
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
            'links',
            'meta' => [
                'pagination' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                    'from',
                    'to',
                ],
            ],
        ])
        ->assertJsonCount(15, 'data');
});

it('librarian can create a category with valid data', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $this->postJson($this->endpoint, ['name' => 'Science Fiction', 'description' => 'Sci-fi books'])
        ->assertCreated()
        ->assertJsonPath('data.type', 'categories')
        ->assertJsonPath('data.attributes.name', 'Science Fiction');
});

it('admin can create a category', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Sanctum::actingAs($admin);

    $this->postJson($this->endpoint, ['name' => 'Horror'])
        ->assertCreated()
        ->assertJsonPath('data.attributes.name', 'Horror');
});

it('returns 201 with category resource on success', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $response = $this->postJson($this->endpoint, ['name' => 'Biography', 'description' => 'Biographies']);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['type', 'id', 'attributes' => ['name', 'description']],
            'meta',
        ]);

    expect(Category::query()->where('name', 'Biography')->exists())->toBeTrue();
});

it('returns 422 if name is missing', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $this->postJson($this->endpoint, ['description' => 'No name provided'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('returns 422 if name already exists', function (): void {
    Category::factory()->create(['name' => 'Duplicate']);

    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $this->postJson($this->endpoint, ['name' => 'Duplicate'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('returns 403 for user role on create', function (): void {
    $user = User::factory()->create(['role' => UserRole::User]);
    Sanctum::actingAs($user);

    $this->postJson($this->endpoint, ['name' => 'Should Fail'])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
});

it('returns 401 for unauthenticated create request', function (): void {
    $this->postJson($this->endpoint, ['name' => 'Should Fail'])
        ->assertUnauthorized();
});
