<?php

declare(strict_types=1);

use App\Contracts\FineGenerator;
use App\DTOs\Fine\GenerateFineDTO;
use App\Enums\BookCopyStatus;
use App\Enums\FineType;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\BookAvailableNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

/**
 * Records every fine the return flow asks for, so assertions can inspect the
 * type and amount without Module 9's persistence being in place yet.
 */
function fineGeneratorSpy(): object
{
    $spy = new class implements FineGenerator
    {
        /** @var list<GenerateFineDTO> */
        public array $generated = [];

        public function generate(GenerateFineDTO $dto): void
        {
            $this->generated[] = $dto;
        }
    };

    app()->instance(FineGenerator::class, $spy);

    return $spy;
}

beforeEach(function (): void {
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->member = User::factory()->create(['role' => UserRole::User]);

    $this->book = Book::factory()->create();
    $this->copy = BookCopy::factory()->create([
        'book_id' => $this->book->id,
        'status' => BookCopyStatus::Loaned,
    ]);

    $this->loan = Loan::factory()->active()->create([
        'user_id' => $this->member->id,
        'book_copy_id' => $this->copy->id,
    ]);

    $this->endpoint = "/api/v1/loans/{$this->loan->id}/return";
});

it('librarian can register a return for an active loan', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.type', 'loans');
});

it('sets the loan status to returned and stamps returned_at', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint)->assertOk();

    $loan = $this->loan->refresh();

    expect($loan->status)->toBe(LoanStatus::Returned);
    expect($loan->returned_at)->not->toBeNull();
});

it('sets the copy status to available on a clean return', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint)->assertOk();

    expect($this->copy->refresh()->status)->toBe(BookCopyStatus::Available);
});

it('generates no fine for an on-time return', function (): void {
    $spy = fineGeneratorSpy();
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint)->assertOk();

    expect($spy->generated)->toBeEmpty();
});

it('generates a late_return fine when returned after the due date', function (): void {
    $spy = fineGeneratorSpy();
    Sanctum::actingAs($this->librarian);

    $this->loan->update(['due_date' => now()->subDays(10)]);

    $this->postJson($this->endpoint)->assertOk();

    expect($spy->generated)->toHaveCount(1);
    expect($spy->generated[0]->type)->toBe(FineType::LateReturn);
    expect($spy->generated[0]->loan_id)->toBe($this->loan->id);
    expect($spy->generated[0]->user_id)->toBe($this->member->id);
});

it('charges 2.00 per overdue day', function (): void {
    $spy = fineGeneratorSpy();
    Sanctum::actingAs($this->librarian);

    $this->loan->update(['due_date' => now()->subDays(10)]);

    $this->postJson($this->endpoint)->assertOk();

    expect($spy->generated[0]->amount)->toBe(20.0);
});

it('caps the late_return fine at 60.00 for 30 or more overdue days', function (): void {
    $spy = fineGeneratorSpy();
    Sanctum::actingAs($this->librarian);

    $this->loan->update(['due_date' => now()->subDays(45)]);

    $this->postJson($this->endpoint)->assertOk();

    expect($spy->generated[0]->amount)->toBe(60.0);
});

it('generates a damage fine and sets the copy to maintenance when damaged', function (): void {
    $spy = fineGeneratorSpy();
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint, ['damaged' => true, 'damage_amount' => 30])
        ->assertOk();

    expect($spy->generated)->toHaveCount(1);
    expect($spy->generated[0]->type)->toBe(FineType::Damage);
    expect($spy->generated[0]->amount)->toBe(30.0);
    expect($this->copy->refresh()->status)->toBe(BookCopyStatus::Maintenance);
});

it('generates both fines when a damaged copy is returned late', function (): void {
    $spy = fineGeneratorSpy();
    Sanctum::actingAs($this->librarian);

    $this->loan->update(['due_date' => now()->subDays(3)]);

    $this->postJson($this->endpoint, ['damaged' => true, 'damage_amount' => 15])
        ->assertOk();

    expect($spy->generated)->toHaveCount(2);
    expect(array_map(fn (GenerateFineDTO $dto): FineType => $dto->type, $spy->generated))
        ->toBe([FineType::LateReturn, FineType::Damage]);
});

it('BookAvailableNotification sent when book has an approved reservation', function (): void {
    Notification::fake();
    Sanctum::actingAs($this->librarian);

    $waiting = User::factory()->create(['role' => UserRole::User]);
    Reservation::factory()->approved()->create([
        'book_id' => $this->book->id,
        'user_id' => $waiting->id,
    ]);

    $this->postJson($this->endpoint)->assertOk();

    Notification::assertSentTo($waiting, BookAvailableNotification::class);
});

it('no notification sent when book has no approved reservations', function (): void {
    Notification::fake();
    Sanctum::actingAs($this->librarian);

    Reservation::factory()->pending()->create(['book_id' => $this->book->id]);

    $this->postJson($this->endpoint)->assertOk();

    Notification::assertNothingSent();
});

it('announces no availability when the returned copy goes to maintenance', function (): void {
    Notification::fake();
    Sanctum::actingAs($this->librarian);

    $waiting = User::factory()->create(['role' => UserRole::User]);
    Reservation::factory()->approved()->create([
        'book_id' => $this->book->id,
        'user_id' => $waiting->id,
    ]);

    $this->postJson($this->endpoint, ['damaged' => true, 'damage_amount' => 20])
        ->assertOk();

    Notification::assertNothingSent();
});

it('returns 422 when damaged is true without a damage amount', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint, ['damaged' => true])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('returns 422 when the damage amount falls outside the 5 to 50 range', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson($this->endpoint, ['damaged' => true, 'damage_amount' => 80])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('returns 422 LOAN_ALREADY_RETURNED when the loan is already returned', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->loan->update(['status' => LoanStatus::Returned, 'returned_at' => now()]);

    $this->postJson($this->endpoint)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'LOAN_ALREADY_RETURNED');
});

it('returns 403 for user role', function (): void {
    Sanctum::actingAs($this->member);

    $this->postJson($this->endpoint)->assertForbidden();
});

it('returns 401 for unauthenticated request', function (): void {
    $this->postJson($this->endpoint)->assertUnauthorized();
});
