<?php

declare(strict_types=1);

use App\Actions\Fine\ReconcileAccountSuspensionAction;
use App\Actions\Fine\WaiveFineAction;
use App\DTOs\Fine\WaiveFineDTO;
use App\Enums\FineStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\Fine\FineAlreadyClosedException;
use App\Exceptions\Fine\WaiveLimitExceededException;
use App\Models\Fine;
use App\Models\User;

// Every audited action records who performed it, so these unit tests act as a
// user the same way the HTTP routes behind them always do.
beforeEach(function (): void {
    test()->actingAs(User::factory()->create());

    $this->action = new WaiveFineAction(new ReconcileAccountSuspensionAction());
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
});

it('throws WaiveLimitExceededException for librarian on fine > 20', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 20.01]);

    expect(fn (): Fine => $this->action->execute($fine, new WaiveFineDTO('Goodwill.'), $this->librarian))
        ->toThrow(WaiveLimitExceededException::class);
});

it('allows a librarian to waive a fine at the 20 limit', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 20.0]);

    $result = $this->action->execute($fine, new WaiveFineDTO('Goodwill.'), $this->librarian);

    expect($result->status)->toBe(FineStatus::Waived);
});

it('allows admin to waive fine of any amount', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 500.0]);

    $result = $this->action->execute($fine, new WaiveFineDTO('Approved by management.'), $this->admin);

    expect($result->status)->toBe(FineStatus::Waived);
});

it('sets correct waived_by and waived_reason', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->member->id, 'amount' => 15.0]);

    $result = $this->action->execute($fine, new WaiveFineDTO('Copy was already damaged.'), $this->librarian);

    expect($result->waived_by)->toBe($this->librarian->id);
    expect($result->waived_reason)->toBe('Copy was already damaged.');
});

it('throws FineAlreadyClosedException for a fine that is already settled', function (string $state): void {
    $fine = Fine::factory()->{$state}()->create(['user_id' => $this->member->id, 'amount' => 15.0]);

    expect(fn (): Fine => $this->action->execute($fine, new WaiveFineDTO('Goodwill.'), $this->admin))
        ->toThrow(FineAlreadyClosedException::class);
})->with(['paid', 'waived']);

it('restores a suspended account when the waived fine clears its debt', function (): void {
    $borrower = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Suspended]);
    $fine = Fine::factory()->pending()->create(['user_id' => $borrower->id, 'amount' => 120.0]);

    $this->action->execute($fine, new WaiveFineDTO('Library error.'), $this->admin);

    expect($borrower->refresh()->status)->toBe(UserStatus::Active);
});
