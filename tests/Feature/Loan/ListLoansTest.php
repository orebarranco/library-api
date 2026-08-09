<?php

declare(strict_types=1);

use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\Loan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/loans';
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
    $this->other = User::factory()->create(['role' => UserRole::User]);
});

it('user sees only their own loans', function (): void {
    Loan::factory()->count(2)->create(['user_id' => $this->member->id]);
    Loan::factory()->count(3)->create(['user_id' => $this->other->id]);

    Sanctum::actingAs($this->member);

    $response = $this->getJson($this->endpoint)->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

it('librarian sees all loans', function (): void {
    Loan::factory()->count(2)->create(['user_id' => $this->member->id]);
    Loan::factory()->count(3)->create(['user_id' => $this->other->id]);

    Sanctum::actingAs($this->librarian);

    $response = $this->getJson($this->endpoint)->assertOk();

    expect($response->json('data'))->toHaveCount(5);
});

it('returns a paginated list of 15 per page by default', function (): void {
    Loan::factory()->count(18)->create(['user_id' => $this->member->id]);

    Sanctum::actingAs($this->member);

    $response = $this->getJson($this->endpoint)->assertOk();

    expect($response->json('data'))->toHaveCount(15);
});

it('supports the page parameter', function (): void {
    Loan::factory()->count(18)->create(['user_id' => $this->member->id]);

    Sanctum::actingAs($this->member);

    $response = $this->getJson($this->endpoint.'?page=2')->assertOk();

    expect($response->json('data'))->toHaveCount(3);
});

it('supports filtering by status', function (): void {
    Loan::factory()->active()->count(2)->create(['user_id' => $this->member->id]);
    Loan::factory()->overdue()->count(3)->create(['user_id' => $this->member->id]);
    Loan::factory()->returned()->count(4)->create(['user_id' => $this->member->id]);

    Sanctum::actingAs($this->member);

    expect($this->getJson($this->endpoint.'?filter[status]='.LoanStatus::Active->value)->assertOk()->json('data'))->toHaveCount(2);
    expect($this->getJson($this->endpoint.'?filter[status]='.LoanStatus::Overdue->value)->assertOk()->json('data'))->toHaveCount(3);
    expect($this->getJson($this->endpoint.'?filter[status]='.LoanStatus::Returned->value)->assertOk()->json('data'))->toHaveCount(4);
});

it('returns 401 for unauthenticated request', function (): void {
    $this->getJson($this->endpoint)->assertUnauthorized();
});
