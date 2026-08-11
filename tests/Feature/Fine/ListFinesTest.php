<?php

declare(strict_types=1);

use App\Enums\FineStatus;
use App\Enums\UserRole;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/fines';
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
    $this->other = User::factory()->create(['role' => UserRole::User]);
});

it('user sees only their own fines', function (): void {
    Fine::factory()->count(2)->create(['user_id' => $this->member->id]);
    Fine::factory()->count(3)->create(['user_id' => $this->other->id]);

    Sanctum::actingAs($this->member);

    expect($this->getJson($this->endpoint)->assertOk()->json('data'))->toHaveCount(2);
});

it('librarian sees all fines', function (): void {
    Fine::factory()->count(2)->create(['user_id' => $this->member->id]);
    Fine::factory()->count(3)->create(['user_id' => $this->other->id]);

    Sanctum::actingAs($this->librarian);

    expect($this->getJson($this->endpoint)->assertOk()->json('data'))->toHaveCount(5);
});

it('returns a paginated list of 15 per page by default', function (): void {
    Fine::factory()->count(18)->create(['user_id' => $this->member->id]);

    Sanctum::actingAs($this->member);

    expect($this->getJson($this->endpoint)->assertOk()->json('data'))->toHaveCount(15);
});

it('supports the page parameter', function (): void {
    Fine::factory()->count(18)->create(['user_id' => $this->member->id]);

    Sanctum::actingAs($this->member);

    expect($this->getJson($this->endpoint.'?page=2')->assertOk()->json('data'))->toHaveCount(3);
});

it('supports filtering by status', function (): void {
    Fine::factory()->pending()->count(2)->create(['user_id' => $this->member->id]);
    Fine::factory()->partiallyPaid()->count(3)->create(['user_id' => $this->member->id]);
    Fine::factory()->paid()->count(4)->create(['user_id' => $this->member->id]);
    Fine::factory()->waived()->count(1)->create(['user_id' => $this->member->id]);

    Sanctum::actingAs($this->member);

    expect($this->getJson($this->endpoint.'?filter[status]='.FineStatus::Pending->value)->assertOk()->json('data'))->toHaveCount(2);
    expect($this->getJson($this->endpoint.'?filter[status]='.FineStatus::PartiallyPaid->value)->assertOk()->json('data'))->toHaveCount(3);
    expect($this->getJson($this->endpoint.'?filter[status]='.FineStatus::Paid->value)->assertOk()->json('data'))->toHaveCount(4);
    expect($this->getJson($this->endpoint.'?filter[status]='.FineStatus::Waived->value)->assertOk()->json('data'))->toHaveCount(1);
});

it('response includes related loan information', function (): void {
    $loan = Loan::factory()->create(['user_id' => $this->member->id, 'due_date' => now()->subDays(4)]);
    Fine::factory()->create(['user_id' => $this->member->id, 'loan_id' => $loan->id]);

    Sanctum::actingAs($this->member);

    $response = $this->getJson($this->endpoint.'?include=loan')->assertOk();

    $included = collect($response->json('included'))->firstWhere('type', 'loans');

    expect($included['id'])->toBe($loan->id);
    expect($included['attributes'])->toHaveKeys(['status', 'due_date', 'days_overdue']);
});

it('exposes the fine attributes in JSON:API format', function (): void {
    Fine::factory()->create(['user_id' => $this->member->id, 'amount' => 24.0]);

    Sanctum::actingAs($this->member);

    $response = $this->getJson($this->endpoint)->assertOk();

    expect($response->json('data.0.type'))->toBe('fines');
    expect($response->json('data.0.attributes'))->toHaveKeys([
        'type', 'amount', 'amount_paid', 'balance', 'status', 'description', 'waived_by', 'waived_reason', 'created_at',
    ]);
    // Whole amounts serialize as JSON integers: json_encode drops a zero
    // fraction unless JSON_PRESERVE_ZERO_FRACTION is set.
    expect($response->json('data.0.attributes.amount'))->toBe(24);
    expect($response->json('data.0.attributes.balance'))->toBe(24);
});

it('returns 401 for unauthenticated request', function (): void {
    $this->getJson($this->endpoint)->assertUnauthorized();
});
