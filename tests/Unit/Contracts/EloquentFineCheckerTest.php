<?php

declare(strict_types=1);

use App\Contracts\EloquentFineChecker;
use App\Contracts\FineChecker;
use App\Models\Fine;
use App\Models\User;

beforeEach(function (): void {
    $this->checker = new EloquentFineChecker();
});

it('implements the FineChecker contract', function (): void {
    expect($this->checker)->toBeInstanceOf(FineChecker::class);
});

it('returns the outstanding balance of pending and partially paid fines', function (): void {
    $user = User::factory()->create();
    Fine::factory()->pending()->create(['user_id' => $user->id, 'amount' => 30.0]);
    Fine::factory()->partiallyPaid()->create(['user_id' => $user->id, 'amount' => 20.0]);
    Fine::factory()->paid()->create(['user_id' => $user->id, 'amount' => 90.0]);

    expect($this->checker->pendingFinesTotal($user))->toBe(40.0);
});

it('returns 0.0 for a user with no outstanding fines', function (): void {
    expect($this->checker->pendingFinesTotal(User::factory()->create()))->toBe(0.0);
});

it('is the bound implementation of the contract', function (): void {
    expect(resolve(FineChecker::class))->toBeInstanceOf(EloquentFineChecker::class);
});
