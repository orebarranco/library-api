<?php

declare(strict_types=1);

use App\Actions\Fine\ReconcileAccountSuspensionAction;
use App\Enums\UserStatus;
use App\Models\Fine;
use App\Models\User;

beforeEach(function (): void {
    $this->action = new ReconcileAccountSuspensionAction();
});

it('suspends an active account once outstanding fines reach 100', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    Fine::factory()->pending()->create(['user_id' => $user->id, 'amount' => 100.0]);

    $this->action->execute($user);

    expect($user->refresh()->status)->toBe(UserStatus::Suspended);
});

it('leaves an active account alone below the threshold', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    Fine::factory()->pending()->create(['user_id' => $user->id, 'amount' => 99.99]);

    $this->action->execute($user);

    expect($user->refresh()->status)->toBe(UserStatus::Active);
});

it('keeps a suspended account suspended while it still owes 100 or more', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Suspended]);
    Fine::factory()->pending()->create(['user_id' => $user->id, 'amount' => 120.0]);

    $this->action->execute($user);

    expect($user->refresh()->status)->toBe(UserStatus::Suspended);
});

it('restores a suspended account once outstanding fines fall below 100', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Suspended]);
    Fine::factory()->pending()->create(['user_id' => $user->id, 'amount' => 40.0]);

    $this->action->execute($user);

    expect($user->refresh()->status)->toBe(UserStatus::Active);
});

it('ignores paid and waived fines when measuring the threshold', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    Fine::factory()->paid()->create(['user_id' => $user->id, 'amount' => 80.0]);
    Fine::factory()->waived()->create(['user_id' => $user->id, 'amount' => 80.0]);

    $this->action->execute($user);

    expect($user->refresh()->status)->toBe(UserStatus::Active);
});
