<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Author;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/authors';
});

it('anyone can list authors without authentication', function (): void {
    Author::factory()->count(3)->create();

    $this->getJson($this->endpoint)
        ->assertSuccessful();
});

it('returns paginated list (default 15 per page)', function (): void {
    Author::factory()->count(20)->create();

    $this->getJson($this->endpoint)
        ->assertSuccessful()
        ->assertJsonCount(15, 'data');
});

it('supports ?page= parameter', function (): void {
    Author::factory()->count(20)->create();

    $this->getJson($this->endpoint.'?page=2')
        ->assertSuccessful()
        ->assertJsonPath('meta.pagination.current_page', 2);
});

it('returns JSON:API format', function (): void {
    Author::factory()->create(['name' => 'Jane Austen', 'biography' => 'English novelist']);

    $this->getJson($this->endpoint)
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [['type', 'id', 'attributes']],
            'meta',
        ])
        ->assertJsonPath('data.0.type', 'authors')
        ->assertJsonPath('data.0.attributes.name', 'Jane Austen')
        ->assertJsonPath('data.0.attributes.biography', 'English novelist');
});

it('librarian can create an author with valid data', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $this->postJson($this->endpoint, [
        'name' => 'Mark Twain',
        'biography' => 'American writer',
        'birth_date' => '1835-11-30',
    ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'authors')
        ->assertJsonPath('data.attributes.name', 'Mark Twain');
});

it('admin can create an author', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Sanctum::actingAs($admin);

    $this->postJson($this->endpoint, ['name' => 'George Orwell'])
        ->assertCreated()
        ->assertJsonPath('data.attributes.name', 'George Orwell');
});

it('returns 201 with author resource on success', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $response = $this->postJson($this->endpoint, [
        'name' => 'Ernest Hemingway',
        'biography' => 'American novelist',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['type', 'id', 'attributes' => ['name', 'biography', 'birth_date']],
            'meta',
        ]);

    expect(Author::query()->where('name', 'Ernest Hemingway')->exists())->toBeTrue();
});

it('librarian can update an author', function (): void {
    $author = Author::factory()->create(['name' => 'Old Name']);
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $this->putJson("{$this->endpoint}/{$author->id}", ['name' => 'New Name'])
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.name', 'New Name');

    expect($author->fresh()->name)->toBe('New Name');
});

it('librarian can update an author with all fields', function (): void {
    $author = Author::factory()->create();
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $this->putJson("{$this->endpoint}/{$author->id}", [
        'name' => 'Updated Name',
        'biography' => 'Updated biography',
        'birth_date' => '1900-06-15',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.name', 'Updated Name')
        ->assertJsonPath('data.attributes.biography', 'Updated biography')
        ->assertJsonPath('data.attributes.birth_date', '1900-06-15T00:00:00.000000Z');
});

it('returns 404 for non-existent author on update', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $this->putJson("{$this->endpoint}/non-existent-id", ['name' => 'New Name'])
        ->assertNotFound();
});

it('returns 422 if name is missing on create', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    Sanctum::actingAs($librarian);

    $this->postJson($this->endpoint, ['biography' => 'No name provided'])
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

it('returns 403 for user role on update', function (): void {
    $author = Author::factory()->create();
    $user = User::factory()->create(['role' => UserRole::User]);
    Sanctum::actingAs($user);

    $this->putJson("{$this->endpoint}/{$author->id}", ['name' => 'Should Fail'])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
});

it('returns 401 for unauthenticated create request', function (): void {
    $this->postJson($this->endpoint, ['name' => 'Should Fail'])
        ->assertUnauthorized();
});
