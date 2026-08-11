<?php

declare(strict_types=1);

use App\Actions\Fine\PayFineAction;
use App\Actions\Fine\ReconcileAccountSuspensionAction;
use App\DTOs\Fine\PayFineDTO;
use App\Enums\FineStatus;
use App\Enums\UserStatus;
use App\Exceptions\Fine\FineAlreadyClosedException;
use App\Exceptions\Fine\PaymentExceedsBalanceException;
use App\Models\Fine;
use App\Models\User;

beforeEach(function (): void {
    $this->action = new PayFineAction(new ReconcileAccountSuspensionAction());
    $this->user = User::factory()->create(['status' => UserStatus::Active]);
});

it('correctly updates amount_paid and sets partially_paid status', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->user->id, 'amount' => 40.0]);

    $result = $this->action->execute($fine, new PayFineDTO(15.0));

    expect($result->amount_paid)->toBe(15.0);
    expect($result->status)->toBe(FineStatus::PartiallyPaid);
    expect($result->balance)->toBe(25.0);
});

it('correctly sets paid status when full amount is paid', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->user->id, 'amount' => 40.0]);

    $result = $this->action->execute($fine, new PayFineDTO(40.0));

    expect($result->amount_paid)->toBe(40.0);
    expect($result->status)->toBe(FineStatus::Paid);
    expect($result->balance)->toBe(0.0);
});

it('accumulates on top of an earlier partial payment', function (): void {
    $fine = Fine::factory()->partiallyPaid()->create(['user_id' => $this->user->id, 'amount' => 40.0]);

    $result = $this->action->execute($fine, new PayFineDTO(5.0));

    expect($result->amount_paid)->toBe(25.0);
    expect($result->status)->toBe(FineStatus::PartiallyPaid);
});

it('settles a partially paid fine when the remaining balance is paid', function (): void {
    $fine = Fine::factory()->partiallyPaid()->create(['user_id' => $this->user->id, 'amount' => 40.0]);

    $result = $this->action->execute($fine, new PayFineDTO(20.0));

    expect($result->status)->toBe(FineStatus::Paid);
});

it('throws PaymentExceedsBalanceException when amount is too high', function (): void {
    $fine = Fine::factory()->pending()->create(['user_id' => $this->user->id, 'amount' => 40.0]);

    expect(fn (): Fine => $this->action->execute($fine, new PayFineDTO(40.01)))
        ->toThrow(PaymentExceedsBalanceException::class);
});

it('throws PaymentExceedsBalanceException against the remaining balance, not the amount', function (): void {
    $fine = Fine::factory()->partiallyPaid()->create(['user_id' => $this->user->id, 'amount' => 40.0]);

    expect(fn (): Fine => $this->action->execute($fine, new PayFineDTO(25.0)))
        ->toThrow(PaymentExceedsBalanceException::class);
});

it('throws FineAlreadyClosedException when fine is paid or waived', function (string $state): void {
    $fine = Fine::factory()->{$state}()->create(['user_id' => $this->user->id, 'amount' => 40.0]);

    expect(fn (): Fine => $this->action->execute($fine, new PayFineDTO(5.0)))
        ->toThrow(FineAlreadyClosedException::class);
})->with(['paid', 'waived']);

it('unsuspends account when total pending fines drop below 100 after payment', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Suspended]);
    $fine = Fine::factory()->pending()->create(['user_id' => $user->id, 'amount' => 100.0]);

    $this->action->execute($fine, new PayFineDTO(1.0));

    expect($user->refresh()->status)->toBe(UserStatus::Active);
});

it('keeps the account suspended while the remaining debt is still 100 or more', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Suspended]);
    Fine::factory()->pending()->create(['user_id' => $user->id, 'amount' => 90.0]);
    $fine = Fine::factory()->pending()->create(['user_id' => $user->id, 'amount' => 40.0]);

    $this->action->execute($fine, new PayFineDTO(10.0));

    expect($user->refresh()->status)->toBe(UserStatus::Suspended);
});
