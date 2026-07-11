<?php

declare(strict_types=1);

use App\Contracts\LoanChecker;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\mock;

beforeEach(function (): void {
    $this->book = Book::factory()->create(['isbn' => '9780132350884']);
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->user = User::factory()->create(['role' => UserRole::User]);
    $this->storeEndpoint = "/api/v1/books/{$this->book->id}/copies";
});

// ─────────────────────────────────────────────
// STORE
// ─────────────────────────────────────────────

it('librarian can add a copy to a book', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->storeEndpoint)
        ->assertCreated();
});

it('returns 201 with book copy resource on success', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->storeEndpoint)
        ->assertCreated()
        ->assertJsonPath('data.type', 'book-copies')
        ->assertJsonStructure([
            'data' => ['type', 'id', 'attributes' => ['code', 'status']],
            'meta',
        ]);
});

it('copy code is auto-generated in correct format', function (): void {
    Sanctum::actingAs($this->librarian);

    $response = $this->postJson($this->storeEndpoint)->assertCreated();

    $code = $response->json('data.attributes.code');
    expect($code)->toMatch('/^\d{6}-\d{3}$/');
});

it('first copy code ends in 001', function (): void {
    Sanctum::actingAs($this->librarian);

    $response = $this->postJson($this->storeEndpoint)->assertCreated();

    expect($response->json('data.attributes.code'))->toEndWith('-001');
});

it('second copy code ends in 002', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->storeEndpoint)->assertCreated();
    $response = $this->postJson($this->storeEndpoint)->assertCreated();

    expect($response->json('data.attributes.code'))->toEndWith('-002');
});

it('copy code uses last 6 digits of isbn', function (): void {
    Sanctum::actingAs($this->librarian);

    // isbn 9780132350884 → digits 9780132350884 → last 6 = 350884
    $response = $this->postJson($this->storeEndpoint)->assertCreated();

    expect($response->json('data.attributes.code'))->toStartWith('350884-');
});

// ─────────────────────────────────────────────
// UPDATE STATUS
// ─────────────────────────────────────────────

it('librarian can change copy status to maintenance', function (): void {
    Sanctum::actingAs($this->librarian);

    $copy = BookCopy::factory()->create(['book_id' => $this->book->id]);

    $this->putJson("/api/v1/book-copies/{$copy->id}/status", ['status' => 'maintenance'])
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'maintenance');
});

it('librarian can change copy status to lost', function (): void {
    Sanctum::actingAs($this->librarian);

    $copy = BookCopy::factory()->create(['book_id' => $this->book->id]);

    $this->putJson("/api/v1/book-copies/{$copy->id}/status", ['status' => 'lost'])
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'lost');
});

it('cannot change status to loaned directly', function (): void {
    Sanctum::actingAs($this->librarian);

    $copy = BookCopy::factory()->create(['book_id' => $this->book->id]);

    $this->putJson("/api/v1/book-copies/{$copy->id}/status", ['status' => 'loaned'])
        ->assertUnprocessable();
});

// ─────────────────────────────────────────────
// DELETE
// ─────────────────────────────────────────────

it('cannot delete a copy with an active loan — returns 409 COPY_HAS_ACTIVE_LOAN', function (): void {
    Sanctum::actingAs($this->librarian);

    $copy = BookCopy::factory()->create(['book_id' => $this->book->id]);

    mock(LoanChecker::class)
        ->shouldReceive('hasActiveLoanForCopy')
        ->andReturn(true)
        ->shouldReceive('hasActiveLoans')
        ->andReturn(false);

    $this->deleteJson("/api/v1/book-copies/{$copy->id}")
        ->assertStatus(409)
        ->assertJsonPath('errors.0.code', 'COPY_HAS_ACTIVE_LOAN');

    expect(BookCopy::query()->find($copy->id))->not->toBeNull();
});

it('can delete a copy with no active loans', function (): void {
    Sanctum::actingAs($this->librarian);

    $copy = BookCopy::factory()->create(['book_id' => $this->book->id]);

    $this->deleteJson("/api/v1/book-copies/{$copy->id}")
        ->assertNoContent();

    expect(BookCopy::query()->find($copy->id))->toBeNull();
});

// ─────────────────────────────────────────────
// RBAC / AUTH
// ─────────────────────────────────────────────

it('returns 403 for user role on all write operations', function (): void {
    Sanctum::actingAs($this->user);

    $copy = BookCopy::factory()->create(['book_id' => $this->book->id]);

    $this->postJson($this->storeEndpoint)->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');

    $this->putJson("/api/v1/book-copies/{$copy->id}/status", ['status' => 'maintenance'])
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');

    $this->deleteJson("/api/v1/book-copies/{$copy->id}")
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INSUFFICIENT_PERMISSIONS');
});

it('returns 401 for unauthenticated write request', function (): void {
    $this->postJson($this->storeEndpoint)
        ->assertUnauthorized();
});

it('returns 404 when updating status of non-existent copy', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->putJson('/api/v1/book-copies/non-existent-id/status', ['status' => 'available'])
        ->assertNotFound();
});

it('returns 404 when deleting non-existent copy', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->deleteJson('/api/v1/book-copies/non-existent-id')
        ->assertNotFound();
});
