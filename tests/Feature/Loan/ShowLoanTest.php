<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
    $this->other = User::factory()->create(['role' => UserRole::User]);

    $this->book = Book::factory()->create(['title' => 'The Pragmatic Programmer']);
    $this->copy = BookCopy::factory()->create(['book_id' => $this->book->id, 'code' => 'PP-001']);
    $this->loan = Loan::factory()->create([
        'user_id' => $this->member->id,
        'book_copy_id' => $this->copy->id,
    ]);
});

it('owner can view their own loan', function (): void {
    Sanctum::actingAs($this->member);

    $this->getJson("/api/v1/loans/{$this->loan->id}")
        ->assertOk()
        ->assertJsonPath('data.type', 'loans')
        ->assertJsonPath('data.id', $this->loan->id);
});

it('response includes book title and copy code when related resources are requested', function (): void {
    Sanctum::actingAs($this->member);

    $response = $this->getJson("/api/v1/loans/{$this->loan->id}?include=bookCopy.book")
        ->assertOk();

    $included = collect($response->json('included'));

    expect($included->firstWhere('type', 'book-copies')['attributes']['code'])->toBe('PP-001');
    expect($included->firstWhere('type', 'books')['attributes']['title'])->toBe('The Pragmatic Programmer');
});

it('user cannot view another users loan and receives 403', function (): void {
    Sanctum::actingAs($this->other);

    $this->getJson("/api/v1/loans/{$this->loan->id}")
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'UNAUTHORIZED');
});

it('librarian can view any loan', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson("/api/v1/loans/{$this->loan->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $this->loan->id);
});

it('returns 404 for a non-existent loan', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson('/api/v1/loans/01JQZZZZZZZZZZZZZZZZZZZZZZ')
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', 'NOT_FOUND');
});

it('returns 401 for unauthenticated request', function (): void {
    $this->getJson("/api/v1/loans/{$this->loan->id}")->assertUnauthorized();
});
