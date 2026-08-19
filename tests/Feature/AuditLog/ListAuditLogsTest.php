<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
});

it('admin can list all audit log entries paginated', function (): void {
    AuditLog::factory()->count(20)->create();

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/v1/audit-logs')->assertOk();

    expect($response->json('data'))->toHaveCount(15)
        ->and($response->json('meta.pagination.total'))->toBe(20)
        ->and($response->json('meta.pagination.per_page'))->toBe(15);
});

it('supports filtering by action', function (): void {
    AuditLog::factory()->count(2)->create(['action' => AuditAction::FineWaived]);
    AuditLog::factory()->count(3)->create(['action' => AuditAction::LoanCreated]);

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/v1/audit-logs?filter[action]=fine.waived')->assertOk();

    expect($response->json('data'))->toHaveCount(2)
        ->and(collect($response->json('data'))->pluck('attributes.action')->unique()->all())
        ->toBe(['fine.waived']);
});

it('supports filtering by user_id', function (): void {
    $actor = User::factory()->create();
    AuditLog::factory()->count(2)->for($actor)->create();
    AuditLog::factory()->count(3)->create();

    Sanctum::actingAs($this->admin);

    $response = $this->getJson("/api/v1/audit-logs?filter[user_id]={$actor->id}")->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

it('supports filtering by model_type', function (): void {
    AuditLog::factory()->count(2)->create(['model_type' => 'Loan']);
    AuditLog::factory()->count(3)->create(['model_type' => 'Book']);

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/v1/audit-logs?filter[model_type]=Loan')->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

it('response uses JSON:API format', function (): void {
    $entry = AuditLog::factory()->create();

    Sanctum::actingAs($this->admin);

    $this->getJson('/api/v1/audit-logs')
        ->assertOk()
        ->assertJsonPath('data.0.type', 'audit-logs')
        ->assertJsonPath('data.0.id', $entry->id)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'action',
                        'model_type',
                        'model_id',
                        'old_values',
                        'new_values',
                        'ip_address',
                        'created_at',
                    ],
                ],
            ],
            'meta' => ['request_id', 'version', 'timestamp', 'pagination'],
        ]);
});

it('exposes the acting user as a JSON:API relationship', function (): void {
    $actor = User::factory()->create();
    AuditLog::factory()->for($actor)->create();

    Sanctum::actingAs($this->admin);

    $this->getJson('/api/v1/audit-logs?include=user')
        ->assertOk()
        ->assertJsonPath('data.0.relationships.user.data.id', $actor->id)
        ->assertJsonPath('included.0.type', 'users');
});

it('lists the newest entry first', function (): void {
    $older = AuditLog::factory()->create(['created_at' => now()->subDay()]);
    $newer = AuditLog::factory()->create(['created_at' => now()]);

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/v1/audit-logs')->assertOk();

    expect($response->json('data.0.id'))->toBe($newer->id)
        ->and($response->json('data.1.id'))->toBe($older->id);
});

it('returns 403 for librarian role', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson('/api/v1/audit-logs')->assertForbidden();
});

it('returns 403 for user role', function (): void {
    Sanctum::actingAs($this->member);

    $this->getJson('/api/v1/audit-logs')->assertForbidden();
});

it('is not writable through the API', function (): void {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/audit-logs', [])->assertStatus(405);
});
