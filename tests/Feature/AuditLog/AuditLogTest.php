<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\BookCopyStatus;
use App\Enums\FineStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Notification::fake();

    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

it('approving a reservation creates an audit log entry', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    Sanctum::actingAs($this->librarian);
    $this->postJson("/api/v1/reservations/{$reservation->id}/approve")->assertOk();

    $entry = AuditLog::query()->sole();

    expect($entry->action)->toBe(AuditAction::ReservationApproved)
        ->and($entry->model_type)->toBe('Reservation')
        ->and($entry->model_id)->toBe($reservation->id)
        ->and($entry->old_values['status'])->toBe(ReservationStatus::Pending->value)
        ->and($entry->new_values['status'])->toBe(ReservationStatus::Approved->value);
});

it('rejecting a reservation creates an audit log entry', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    Sanctum::actingAs($this->librarian);
    $this->postJson("/api/v1/reservations/{$reservation->id}/reject", [
        'reason' => 'No copies left.',
    ])->assertOk();

    expect(AuditLog::query()->sole()->action)->toBe(AuditAction::ReservationRejected);
});

it('creating a loan creates an audit log entry', function (): void {
    $reservation = Reservation::factory()->approved()->create();
    BookCopy::factory()->for($reservation->book)->create(['status' => BookCopyStatus::Available]);

    Sanctum::actingAs($this->librarian);
    $this->postJson('/api/v1/loans', ['reservation_id' => $reservation->id])->assertCreated();

    $entry = AuditLog::query()->sole();

    expect($entry->action)->toBe(AuditAction::LoanCreated)
        ->and($entry->model_type)->toBe('Loan')
        // Nothing existed before, so only the new side carries values.
        ->and($entry->old_values)->toBeNull()
        ->and($entry->new_values['status'])->toBe('active');
});

it('returning a loan creates an audit log entry', function (): void {
    $loan = Loan::factory()->active()->for(Reservation::factory()->completed())->create();

    Sanctum::actingAs($this->librarian);
    $this->postJson("/api/v1/loans/{$loan->id}/return")->assertOk();

    $entry = AuditLog::query()->sole();

    expect($entry->action)->toBe(AuditAction::LoanReturned)
        ->and($entry->new_values['status'])->toBe('returned');
});

it('waiving a fine creates an audit log entry', function (): void {
    $fine = Fine::factory()->create(['amount' => 10.0, 'status' => FineStatus::Pending]);

    Sanctum::actingAs($this->librarian);
    $this->postJson("/api/v1/fines/{$fine->id}/waive", ['reason' => 'Goodwill.'])->assertOk();

    $entry = AuditLog::query()->sole();

    expect($entry->action)->toBe(AuditAction::FineWaived)
        ->and($entry->old_values['status'])->toBe(FineStatus::Pending->value)
        ->and($entry->new_values['status'])->toBe(FineStatus::Waived->value);
});

it('updating a book creates an audit log entry with old and new values', function (): void {
    $book = Book::factory()->create(['title' => 'Old Title']);

    Sanctum::actingAs($this->librarian);
    $this->putJson("/api/v1/books/{$book->id}", ['title' => 'New Title'])->assertOk();

    $entry = AuditLog::query()->sole();

    expect($entry->action)->toBe(AuditAction::BookUpdated)
        ->and($entry->old_values)->toBe(['title' => 'Old Title'])
        ->and($entry->new_values)->toBe(['title' => 'New Title']);
});

it('assigning a role creates an audit log entry', function (): void {
    $member = User::factory()->create(['role' => UserRole::User]);

    Sanctum::actingAs($this->admin);
    $this->putJson("/api/v1/users/{$member->id}/role", ['role' => UserRole::Librarian->value])->assertOk();

    $entry = AuditLog::query()->sole();

    expect($entry->action)->toBe(AuditAction::RoleAssigned)
        ->and($entry->model_type)->toBe('User')
        ->and($entry->old_values)->toBe(['role' => UserRole::User->value])
        ->and($entry->new_values)->toBe(['role' => UserRole::Librarian->value]);
});

it('audit log records acting user id, ip_address and old/new values', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    Sanctum::actingAs($this->librarian);
    $this->postJson("/api/v1/reservations/{$reservation->id}/approve")->assertOk();

    $entry = AuditLog::query()->sole();

    expect($entry->user_id)->toBe($this->librarian->id)
        ->and($entry->ip_address)->not->toBeEmpty()
        ->and($entry->old_values)->toBeArray()
        ->and($entry->new_values)->toBeArray()
        // The write timestamp is the whole point of a trail.
        ->and($entry->created_at)->not->toBeNull();
});

it('does not record a timestamp for updates because entries are never revised', function (): void {
    expect(AuditLog::UPDATED_AT)->toBeNull();
});
