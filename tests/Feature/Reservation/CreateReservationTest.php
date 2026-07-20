<?php

declare(strict_types=1);

use App\Contracts\FineChecker;
use App\Contracts\LoanChecker;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\NewReservationNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\mock;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/reservations';
    $this->user = User::factory()->create(['role' => UserRole::User]);
    $this->book = Book::factory()->create();
    BookCopy::factory()->available()->create(['book_id' => $this->book->id]);
});

it('authenticated user can create a reservation for a book with available copies', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson($this->endpoint, ['book_id' => $this->book->id])
        ->assertCreated();
});

it('returns 201 with JSON:API reservation resource', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson($this->endpoint, ['book_id' => $this->book->id])
        ->assertCreated()
        ->assertJsonPath('data.type', 'reservations')
        ->assertJsonStructure([
            'data' => ['type', 'id', 'attributes' => ['status', 'reserved_at']],
            'meta',
        ]);
});

it('reservation is created with pending status and reserved_at timestamp', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->postJson($this->endpoint, ['book_id' => $this->book->id])
        ->assertCreated();

    expect($response->json('data.attributes.status'))->toBe(ReservationStatus::Pending->value);
    expect($response->json('data.attributes.reserved_at'))->not->toBeNull();

    $this->assertDatabaseHas('reservations', [
        'user_id' => $this->user->id,
        'book_id' => $this->book->id,
        'status' => ReservationStatus::Pending->value,
    ]);
});

it('returns 422 NO_COPIES_AVAILABLE when book has no available copies', function (): void {
    Sanctum::actingAs($this->user);

    BookCopy::query()->where('book_id', $this->book->id)->update(['status' => 'loaned']);

    $this->postJson($this->endpoint, ['book_id' => $this->book->id])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'NO_COPIES_AVAILABLE');
});

it('returns 422 DUPLICATE_RESERVATION when user already has active reservation for same book', function (): void {
    Sanctum::actingAs($this->user);

    Reservation::factory()->pending()->create(['user_id' => $this->user->id, 'book_id' => $this->book->id]);

    $this->postJson($this->endpoint, ['book_id' => $this->book->id])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'DUPLICATE_RESERVATION');
});

it('returns 422 RESERVATION_LIMIT when user already has 3 active reservations', function (): void {
    Sanctum::actingAs($this->user);

    Reservation::factory()->pending()->count(3)->create(['user_id' => $this->user->id]);

    $this->postJson($this->endpoint, ['book_id' => $this->book->id])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'RESERVATION_LIMIT');
});

it('returns 422 UNPAID_FINES when user has pending fines >= 50', function (): void {
    Sanctum::actingAs($this->user);

    mock(FineChecker::class)->shouldReceive('pendingFinesTotal')->andReturn(50.0);

    $this->postJson($this->endpoint, ['book_id' => $this->book->id])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'UNPAID_FINES');
});

it('returns 422 OVERDUE_LOANS when user has overdue loans', function (): void {
    Sanctum::actingAs($this->user);

    mock(FineChecker::class)->shouldReceive('pendingFinesTotal')->andReturn(0.0);
    mock(LoanChecker::class)->shouldReceive('hasOverdueLoans')->andReturn(true)
        ->shouldReceive('hasActiveLoans')->andReturn(false)
        ->shouldReceive('hasActiveLoanForCopy')->andReturn(false);

    $this->postJson($this->endpoint, ['book_id' => $this->book->id])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'OVERDUE_LOANS');
});

it('returns 422 if book_id is missing', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson($this->endpoint, [])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('returns 401 for unauthenticated request', function (): void {
    $this->postJson($this->endpoint, ['book_id' => $this->book->id])
        ->assertUnauthorized();
});

it('librarians receive database notification on reservation creation, admins do not', function (): void {
    Notification::fake();

    Sanctum::actingAs($this->user);

    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->postJson($this->endpoint, ['book_id' => $this->book->id])
        ->assertCreated();

    Notification::assertSentTo($librarian, NewReservationNotification::class);
    Notification::assertNotSentTo($admin, NewReservationNotification::class);
});
