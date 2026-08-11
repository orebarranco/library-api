<?php

declare(strict_types=1);

use App\Enums\FineStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Fine;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->member = User::factory()->create(['role' => UserRole::User]);
    $this->other = User::factory()->create(['role' => UserRole::User]);
    $this->fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 40.0]);
    $this->endpoint = "/api/v1/fines/{$this->fine->id}/pay";
});

it('user can make a partial payment on their fine', function (): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint, ['amount' => 15])
        ->assertOk()
        ->assertJsonPath('data.type', 'fines')
        ->assertJsonPath('data.attributes.balance', 25);
});

it('status changes to partially_paid after partial payment', function (): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint, ['amount' => 15])->assertOk();

    expect($this->fine->refresh()->status)->toBe(FineStatus::PartiallyPaid);
});

it('status changes to paid after full payment', function (): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint, ['amount' => 40])->assertOk();

    expect($this->fine->refresh()->status)->toBe(FineStatus::Paid);
});

it('amount_paid is updated correctly', function (): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint, ['amount' => 12.5])->assertOk();

    expect($this->fine->refresh()->amount_paid)->toBe(12.5);
});

it('returns 422 PAYMENT_EXCEEDS_BALANCE if amount exceeds remaining balance', function (): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint, ['amount' => 40.01])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'PAYMENT_EXCEEDS_BALANCE');
});

it('returns 422 FINE_ALREADY_CLOSED if fine is already paid or waived', function (string $state): void {
    $fine = Fine::factory()->{$state}()->create(['user_id' => $this->member->id, 'amount' => 40.0]);

    Sanctum::actingAs($this->member);

    $this->postJson("/api/v1/fines/{$fine->id}/pay", ['amount' => 5])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'FINE_ALREADY_CLOSED');
})->with(['paid', 'waived']);

it('returns 403 if user tries to pay another users fine', function (): void {
    Sanctum::actingAs($this->other);

    $this->postJson($this->endpoint, ['amount' => 5])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'UNAUTHORIZED');
});

it('returns 422 when the amount is missing or not positive', function (mixed $amount): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint, $amount === null ? [] : ['amount' => $amount])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
})->with([null, 0, -5]);

it('reactivates a suspended account when the payment clears the 100 threshold', function (): void {
    $borrower = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Suspended]);
    $fine = Fine::factory()->pending()->create(['user_id' => $borrower->id, 'amount' => 110.0]);

    Sanctum::actingAs($borrower);

    $this->postJson("/api/v1/fines/{$fine->id}/pay", ['amount' => 20])->assertOk();

    expect($borrower->refresh()->status)->toBe(UserStatus::Active);
});

it('librarian can register a payment for any borrower', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);

    Sanctum::actingAs($librarian);

    $this->postJson($this->endpoint, ['amount' => 40])->assertOk();

    expect($this->fine->refresh()->status)->toBe(FineStatus::Paid);
});

it('returns 401 for unauthenticated request', function (): void {
    $this->postJson($this->endpoint, ['amount' => 5])->assertUnauthorized();
});
