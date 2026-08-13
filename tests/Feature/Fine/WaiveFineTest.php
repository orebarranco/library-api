<?php

declare(strict_types=1);

use App\Enums\FineStatus;
use App\Enums\UserRole;
use App\Models\Fine;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
});

function waiveEndpoint(Fine $fine): string
{
    return "/api/v1/fines/{$fine->id}/waive";
}

it('librarian can waive a fine with amount <= 20', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 20.0]);

    Sanctum::actingAs($this->librarian);

    $this->postJson(waiveEndpoint($fine), ['reason' => 'Copy was already damaged.'])
        ->assertOk()
        ->assertJsonPath('data.attributes.status', FineStatus::Waived->value);
});

it('admin can waive a fine of any amount', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 500.0]);

    Sanctum::actingAs($this->admin);

    $this->postJson(waiveEndpoint($fine), ['reason' => 'Approved by management.'])->assertOk();

    expect($fine->refresh()->status)->toBe(FineStatus::Waived);
});

it('fine status is set to waived', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 10.0]);

    Sanctum::actingAs($this->librarian);

    $this->postJson(waiveEndpoint($fine), ['reason' => 'Goodwill gesture.'])->assertOk();

    expect($fine->refresh()->status)->toBe(FineStatus::Waived);
});

it('waived_by is set to the acting user id and waived_reason is recorded', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 10.0]);

    Sanctum::actingAs($this->librarian);

    $this->postJson(waiveEndpoint($fine), ['reason' => 'Library closed on the due date.'])->assertOk();

    $fine->refresh();

    expect($fine->waived_by)->toBe($this->librarian->id);
    expect($fine->waived_reason)->toBe('Library closed on the due date.');
});

it('returns 422 WAIVE_LIMIT_EXCEEDED when librarian tries to waive fine > 20', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 45.0]);

    Sanctum::actingAs($this->librarian);

    $this->postJson(waiveEndpoint($fine), ['reason' => 'Goodwill gesture.'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'WAIVE_LIMIT_EXCEEDED');
});

it('returns 422 if reason is missing', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 10.0]);

    Sanctum::actingAs($this->librarian);

    $this->postJson(waiveEndpoint($fine), [])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('returns 422 FINE_ALREADY_CLOSED for a fine that is already settled', function (): void {
    $fine = Fine::factory()->paid()->create(['user_id' => $this->member->id, 'amount' => 10.0]);

    Sanctum::actingAs($this->admin);

    $this->postJson(waiveEndpoint($fine), ['reason' => 'Goodwill gesture.'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'FINE_ALREADY_CLOSED');
});

it('returns 403 for user role', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 10.0]);

    Sanctum::actingAs($this->member);

    $this->postJson(waiveEndpoint($fine), ['reason' => 'Goodwill gesture.'])->assertForbidden();
});

it('returns 401 for unauthenticated request', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 10.0]);

    $this->postJson(waiveEndpoint($fine), ['reason' => 'Goodwill gesture.'])->assertUnauthorized();
});
