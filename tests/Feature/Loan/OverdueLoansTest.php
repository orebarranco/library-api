<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Loan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/loans/overdue';
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
});

it('librarian can list all overdue loans', function (): void {
    Loan::factory()->overdue()->count(3)->create();
    Loan::factory()->active()->count(2)->create();
    Loan::factory()->returned()->count(2)->create();

    Sanctum::actingAs($this->librarian);

    $response = $this->getJson($this->endpoint)->assertOk();

    expect($response->json('data'))->toHaveCount(3);
});

it('returns a paginated list of 15 per page by default', function (): void {
    Loan::factory()->overdue()->count(18)->create();

    Sanctum::actingAs($this->librarian);

    expect($this->getJson($this->endpoint)->assertOk()->json('data'))->toHaveCount(15);
});

it('supports the page parameter', function (): void {
    Loan::factory()->overdue()->count(18)->create();

    Sanctum::actingAs($this->librarian);

    expect($this->getJson($this->endpoint.'?page=2')->assertOk()->json('data'))->toHaveCount(3);
});

it('includes days_overdue in the response', function (): void {
    Loan::factory()->overdue()->create(['due_date' => now()->subDays(7)]);

    Sanctum::actingAs($this->librarian);

    $response = $this->getJson($this->endpoint)->assertOk();

    expect($response->json('data.0.attributes.days_overdue'))->toBe(7);
});

it('exposes borrower contact information when the user is included', function (): void {
    $borrower = User::factory()->create(['role' => UserRole::User, 'email' => 'borrower@example.com']);
    Loan::factory()->overdue()->create(['user_id' => $borrower->id]);

    Sanctum::actingAs($this->librarian);

    $response = $this->getJson($this->endpoint.'?include=user')->assertOk();

    $included = collect($response->json('included'))->firstWhere('type', 'users');

    expect($included['attributes']['email'])->toBe('borrower@example.com');
    expect($included['attributes']['name'])->toBe($borrower->name);
});

it('returns an empty list when no overdue loans exist', function (): void {
    Loan::factory()->active()->count(2)->create();

    Sanctum::actingAs($this->librarian);

    expect($this->getJson($this->endpoint)->assertOk()->json('data'))->toBeEmpty();
});

it('returns 403 for user role', function (): void {
    Sanctum::actingAs($this->member);

    $this->getJson($this->endpoint)->assertForbidden();
});

it('returns 401 for unauthenticated request', function (): void {
    $this->getJson($this->endpoint)->assertUnauthorized();
});
