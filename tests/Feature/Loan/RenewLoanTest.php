<?php

declare(strict_types=1);

use App\Contracts\FineChecker;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\mock;

beforeEach(function (): void {
    $this->member = User::factory()->create(['role' => UserRole::User]);
    $this->other = User::factory()->create(['role' => UserRole::User]);

    $this->book = Book::factory()->create();
    $this->copy = BookCopy::factory()->create(['book_id' => $this->book->id]);

    // The loan's own reservation is already completed, so it never blocks renewal.
    $this->reservation = Reservation::factory()->completed()->create([
        'user_id' => $this->member->id,
        'book_id' => $this->book->id,
    ]);

    $this->loan = Loan::factory()->active()->create([
        'user_id' => $this->member->id,
        'book_copy_id' => $this->copy->id,
        'reservation_id' => $this->reservation->id,
    ]);

    $this->endpoint = "/api/v1/loans/{$this->loan->id}/renew";
});

it('user can renew their own active loan', function (): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.type', 'loans');
});

it('extends due_date by 14 days from today', function (): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint)->assertOk();

    expect($this->loan->refresh()->due_date->isSameDay(now()->addDays(14)))->toBeTrue();
});

it('increments renewal_count by 1', function (): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint)->assertOk();

    expect($this->loan->refresh()->renewal_count)->toBe(1);
});

it('returns 422 RENEWAL_LIMIT_REACHED when renewal_count is already 2', function (): void {
    Sanctum::actingAs($this->member);

    $this->loan->update(['renewal_count' => 2]);

    $this->postJson($this->endpoint)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'RENEWAL_LIMIT_REACHED');
});

it('returns 422 LOAN_OVERDUE when the loan is overdue', function (): void {
    Sanctum::actingAs($this->member);

    $this->loan->update(['due_date' => now()->subDay()]);

    $this->postJson($this->endpoint)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'LOAN_OVERDUE');
});

it('returns 422 BOOK_HAS_RESERVATIONS when the book has pending reservations', function (): void {
    Sanctum::actingAs($this->member);

    Reservation::factory()->pending()->create(['book_id' => $this->book->id]);

    $this->postJson($this->endpoint)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'BOOK_HAS_RESERVATIONS');
});

it('returns 422 BOOK_HAS_RESERVATIONS when the book has approved reservations', function (): void {
    Sanctum::actingAs($this->member);

    Reservation::factory()->approved()->create(['book_id' => $this->book->id]);

    $this->postJson($this->endpoint)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'BOOK_HAS_RESERVATIONS');
});

it('returns 422 UNPAID_FINES when the user has pending fines', function (): void {
    Sanctum::actingAs($this->member);

    mock(FineChecker::class)->shouldReceive('pendingFinesTotal')->andReturn(10.0);

    $this->postJson($this->endpoint)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'UNPAID_FINES');
});

it('returns 422 RENEWAL_TOO_LATE when fewer than 2 days remain before the due date', function (): void {
    Sanctum::actingAs($this->member);

    $this->loan->update(['due_date' => now()->addDay()]);

    $this->postJson($this->endpoint)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'RENEWAL_TOO_LATE');
});

it('returns 403 when a user tries to renew another users loan', function (): void {
    Sanctum::actingAs($this->other);

    $this->postJson($this->endpoint)
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'UNAUTHORIZED');
});

it('returns 401 for unauthenticated request', function (): void {
    $this->postJson($this->endpoint)->assertUnauthorized();
});
