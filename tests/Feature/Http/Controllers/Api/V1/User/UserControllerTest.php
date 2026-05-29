<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/users';
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

describe('index', function (): void {
    it('admin can list all users paginated', function (): void {
        User::factory()->count(5)->create();
        Sanctum::actingAs($this->admin);

        $this->getJson($this->endpoint)
            ->assertSuccessful()
            ->assertJsonPath('meta.pagination.total', 6);
    });

    it('response uses JSON:API format', function (): void {
        Sanctum::actingAs($this->admin);

        $this->getJson($this->endpoint)
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [['type', 'id', 'attributes' => ['name', 'email', 'role', 'status']]],
                'meta' => ['pagination'],
            ])
            ->assertJsonPath('data.0.type', 'users');
    });

    it('requires authentication', function (): void {
        $this->getJson($this->endpoint)
            ->assertUnauthorized()
            ->assertJsonPath('errors.0.code', 'UNAUTHENTICATED');
    });

    it('requires a verified email', function (): void {
        $unverifiedAdmin = User::factory()->unverified()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($unverifiedAdmin);

        $this->getJson($this->endpoint)
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'EMAIL_NOT_VERIFIED');
    });

    it('returns 403 for librarian role', function (): void {
        $librarian = User::factory()->create(['role' => UserRole::Librarian]);
        Sanctum::actingAs($librarian);

        $this->getJson($this->endpoint)
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
    });

    it('returns 403 for user role', function (): void {
        $user = User::factory()->create(['role' => UserRole::User]);
        Sanctum::actingAs($user);

        $this->getJson($this->endpoint)
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
    });

    it('can filter users by name', function (): void {
        User::factory()->create(['name' => 'Alice Smith']);
        User::factory()->create(['name' => 'Bob Jones']);
        Sanctum::actingAs($this->admin);

        $response = $this->getJson($this->endpoint.'?filter[name]=Alice');

        $response->assertSuccessful();
        $names = collect($response->json('data'))->pluck('attributes.name');
        expect($names)->each->toContain('Alice');
        expect($response->json('meta.pagination.total'))->toBe(1);
    });

    it('can filter users by email', function (): void {
        User::factory()->create(['email' => 'alice@example.com']);
        User::factory()->create(['email' => 'bob@example.com']);
        Sanctum::actingAs($this->admin);

        $response = $this->getJson($this->endpoint.'?filter[email]=alice');

        $response->assertSuccessful();
        expect($response->json('meta.pagination.total'))->toBe(1);
    });

    it('can sort users by name ascending', function (): void {
        User::factory()->create(['name' => 'Zebra']);
        User::factory()->create(['name' => 'Apple']);
        Sanctum::actingAs($this->admin);

        $response = $this->getJson($this->endpoint.'?sort=name');

        $response->assertSuccessful();
        $names = collect($response->json('data'))->pluck('attributes.name')->values()->all();
        expect($names)->toBe(collect($names)->sort()->values()->all());
    });

    it('can sort users by name descending', function (): void {
        User::factory()->create(['name' => 'Zebra']);
        User::factory()->create(['name' => 'Apple']);
        Sanctum::actingAs($this->admin);

        $response = $this->getJson($this->endpoint.'?sort=-name');

        $response->assertSuccessful();
        $names = collect($response->json('data'))->pluck('attributes.name')->values()->all();
        expect($names)->toBe(collect($names)->sortDesc()->values()->all());
    });

    it('rejects disallowed sort fields', function (): void {
        Sanctum::actingAs($this->admin);

        $this->getJson($this->endpoint.'?sort=password')
            ->assertBadRequest();
    });
});

describe('store', function (): void {
    it('admin can create a user with a specified role', function (): void {
        Sanctum::actingAs($this->admin);

        $this->postJson($this->endpoint, [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'role' => 'librarian',
        ])
            ->assertCreated()
            ->assertJsonPath('data.attributes.role', 'librarian');

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'librarian']);
    });

    it('returns 201 with user resource on success', function (): void {
        Sanctum::actingAs($this->admin);

        $this->postJson($this->endpoint, [
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'user',
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => ['type', 'id', 'attributes' => ['name', 'email', 'role', 'status']],
                'meta',
            ])
            ->assertJsonPath('data.type', 'users')
            ->assertJsonPath('data.attributes.name', 'John Smith')
            ->assertJsonPath('data.attributes.status', 'active');
    });

    it('returns 422 if email is already taken', function (): void {
        User::factory()->create(['email' => 'taken@example.com']);
        Sanctum::actingAs($this->admin);

        $this->postJson($this->endpoint, [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'role' => 'user',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
    });

    it('returns 422 if required fields are missing', function (string $field): void {
        Sanctum::actingAs($this->admin);

        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
        ];

        unset($payload[$field]);

        $this->postJson($this->endpoint, $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
    })->with(['name', 'email', 'password', 'role']);

    it('returns 403 for librarian role', function (): void {
        $librarian = User::factory()->create(['role' => UserRole::Librarian]);
        Sanctum::actingAs($librarian);

        $this->postJson($this->endpoint, [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'password' => 'password123',
            'role' => 'user',
        ])
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
    });
});

describe('show', function (): void {
    it('admin can view a single user', function (): void {
        $user = User::factory()->create(['name' => 'John Doe']);
        Sanctum::actingAs($this->admin);

        $this->getJson("{$this->endpoint}/{$user->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'users')
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.attributes.name', 'John Doe');
    });

    it('returns 404 for non-existent user', function (): void {
        Sanctum::actingAs($this->admin);

        $this->getJson("{$this->endpoint}/non-existent-id")
            ->assertNotFound();
    });

    it('returns 403 for librarian role', function (): void {
        $user = User::factory()->create();
        $librarian = User::factory()->create(['role' => UserRole::Librarian]);
        Sanctum::actingAs($librarian);

        $this->getJson("{$this->endpoint}/{$user->id}")
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
    });
});

describe('update', function (): void {
    it('admin can update user name and email', function (): void {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
        Sanctum::actingAs($this->admin);

        $this->putJson("{$this->endpoint}/{$user->id}", [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.name', 'New Name')
            ->assertJsonPath('data.attributes.email', 'new@example.com');

        expect($user->fresh()->name)->toBe('New Name')
            ->and($user->fresh()->email)->toBe('new@example.com');
    });

    it('returns 404 for non-existent user', function (): void {
        Sanctum::actingAs($this->admin);

        $this->putJson("{$this->endpoint}/non-existent-id", [
            'name' => 'Someone',
            'email' => 'someone@example.com',
        ])
            ->assertNotFound();
    });

    it('returns 403 for librarian role', function (): void {
        $user = User::factory()->create();
        $librarian = User::factory()->create(['role' => UserRole::Librarian]);
        Sanctum::actingAs($librarian);

        $this->putJson("{$this->endpoint}/{$user->id}", [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
    });
});

describe('destroy', function (): void {
    it('admin can soft-delete a user', function (): void {
        $user = User::factory()->create();
        Sanctum::actingAs($this->admin);

        $this->deleteJson("{$this->endpoint}/{$user->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    });

    it('soft-deleted user does not appear in listing', function (): void {
        $user = User::factory()->create();
        Sanctum::actingAs($this->admin);

        $this->deleteJson("{$this->endpoint}/{$user->id}")->assertNoContent();

        $this->getJson($this->endpoint)
            ->assertSuccessful()
            ->assertJsonMissing(['id' => $user->id]);
    });

    it('returns 403 for librarian role', function (): void {
        $user = User::factory()->create();
        $librarian = User::factory()->create(['role' => UserRole::Librarian]);
        Sanctum::actingAs($librarian);

        $this->deleteJson("{$this->endpoint}/{$user->id}")
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
    });
});
