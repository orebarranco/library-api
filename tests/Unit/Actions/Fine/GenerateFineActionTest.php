<?php

declare(strict_types=1);

use App\Actions\Fine\GenerateFineAction;
use App\Actions\Fine\ReconcileAccountSuspensionAction;
use App\Contracts\FineGenerator;
use App\DTOs\Fine\GenerateFineDTO;
use App\Enums\FineStatus;
use App\Enums\FineType;
use App\Enums\UserStatus;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;

beforeEach(function (): void {
    $this->action = new GenerateFineAction(new ReconcileAccountSuspensionAction());
    $this->user = User::factory()->create(['status' => UserStatus::Active]);
});

it('implements the FineGenerator contract', function (): void {
    expect($this->action)->toBeInstanceOf(FineGenerator::class);
});

it('persists a pending fine from the DTO', function (): void {
    $loan = Loan::factory()->create(['user_id' => $this->user->id]);

    $fine = $this->action->execute(new GenerateFineDTO(
        user_id: $this->user->id,
        type: FineType::LateReturn,
        amount: 24.0,
        description: 'Late return: 12 day(s) overdue.',
        loan_id: $loan->id,
    ));

    expect($fine->exists)->toBeTrue();
    expect($fine->status)->toBe(FineStatus::Pending);
    expect($fine->type)->toBe(FineType::LateReturn);
    expect($fine->amount)->toBe(24.0);
    expect($fine->amount_paid)->toBe(0.0);
    expect($fine->user_id)->toBe($this->user->id);
    expect($fine->loan_id)->toBe($loan->id);
    expect($fine->description)->toBe('Late return: 12 day(s) overdue.');
});

it('persists a fine that is not tied to a loan', function (): void {
    $fine = $this->action->execute(new GenerateFineDTO(
        user_id: $this->user->id,
        type: FineType::Loss,
        amount: 45.0,
        description: 'Copy reported lost.',
    ));

    expect($fine->loan_id)->toBeNull();
});

it('generate persists the fine through the contract entry point', function (): void {
    $this->action->generate(new GenerateFineDTO(
        user_id: $this->user->id,
        type: FineType::Damage,
        amount: 15.0,
        description: 'Copy returned damaged.',
    ));

    expect(Fine::query()->where('user_id', $this->user->id)->count())->toBe(1);
});

it('suspends the account when the new fine takes outstanding debt to 100', function (): void {
    Fine::factory()->pending()->create(['user_id' => $this->user->id, 'amount' => 60.0]);

    $this->action->execute(new GenerateFineDTO(
        user_id: $this->user->id,
        type: FineType::Damage,
        amount: 40.0,
        description: 'Copy returned damaged.',
    ));

    expect($this->user->refresh()->status)->toBe(UserStatus::Suspended);
});

it('leaves the account active when outstanding debt stays below 100', function (): void {
    $this->action->execute(new GenerateFineDTO(
        user_id: $this->user->id,
        type: FineType::LateReturn,
        amount: 60.0,
        description: 'Late return: 30 day(s) overdue.',
    ));

    expect($this->user->refresh()->status)->toBe(UserStatus::Active);
});
