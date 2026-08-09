<?php

declare(strict_types=1);

use App\Enums\BookCopyStatus;
use App\Enums\LoanStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/loans';
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
    $this->book = Book::factory()->create();
    $this->copy = BookCopy::factory()->available()->create(['book_id' => $this->book->id]);
    $this->reservation = Reservation::factory()->approved()->create([
        'user_id' => $this->member->id,
        'book_id' => $this->book->id,
    ]);
});

it('librarian can create a loan from an approved reservation', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint, ['reservation_id' => $this->reservation->id])
        ->assertCreated();
});

it('returns 201 with JSON:API loan resource', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint, ['reservation_id' => $this->reservation->id])
        ->assertCreated()
        ->assertJsonPath('data.type', 'loans')
        ->assertJsonStructure([
            'data' => ['type', 'id', 'attributes' => ['status', 'loaned_at', 'due_date', 'renewal_count']],
            'meta',
        ]);
});

it('sets loaned_at to now and due_date to now plus 14 days', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint, ['reservation_id' => $this->reservation->id])
        ->assertCreated();

    $loan = $this->reservation->loan()->firstOrFail();

    expect((int) $loan->loaned_at->diffInDays($loan->due_date, absolute: true))->toBe(14);
    expect($loan->loaned_at->isToday())->toBeTrue();
});

it('creates the loan with active status and zero renewals', function (): void {
    Sanctum::actingAs($this->librarian);

    $response = $this->postJson($this->endpoint, ['reservation_id' => $this->reservation->id])
        ->assertCreated();

    expect($response->json('data.attributes.status'))->toBe(LoanStatus::Active->value);
    expect($response->json('data.attributes.renewal_count'))->toBe(0);

    $this->assertDatabaseHas('loans', [
        'user_id' => $this->member->id,
        'book_copy_id' => $this->copy->id,
        'reservation_id' => $this->reservation->id,
        'status' => LoanStatus::Active->value,
    ]);
});

it('updates the book copy status to loaned', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint, ['reservation_id' => $this->reservation->id])
        ->assertCreated();

    expect($this->copy->refresh()->status)->toBe(BookCopyStatus::Loaned);
});

it('updates the reservation status to completed', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint, ['reservation_id' => $this->reservation->id])
        ->assertCreated();

    expect($this->reservation->refresh()->status)->toBe(ReservationStatus::Completed);
});

it('returns 422 RESERVATION_NOT_APPROVED when the reservation is not approved', function (): void {
    Sanctum::actingAs($this->librarian);

    $pending = Reservation::factory()->pending()->create([
        'user_id' => $this->member->id,
        'book_id' => $this->book->id,
    ]);

    $this->postJson($this->endpoint, ['reservation_id' => $pending->id])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'RESERVATION_NOT_APPROVED');
});

it('returns 422 NO_COPIES_AVAILABLE when no available copy exists', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->copy->update(['status' => BookCopyStatus::Loaned]);

    $this->postJson($this->endpoint, ['reservation_id' => $this->reservation->id])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'NO_COPIES_AVAILABLE');
});

it('returns 422 when reservation_id is missing', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint, [])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('returns 403 for user role', function (): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint, ['reservation_id' => $this->reservation->id])
        ->assertForbidden();
});

it('returns 401 for unauthenticated request', function (): void {
    $this->postJson($this->endpoint, ['reservation_id' => $this->reservation->id])
        ->assertUnauthorized();
});
