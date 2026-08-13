<?php

declare(strict_types=1);

use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toContain('id', 'name', 'email', 'email_verified_at', 'role', 'status',
            'created_at', 'updated_at', 'deleted_at')
        ->toHaveCount(9);
});

test('has many reservations', function (): void {
    $user = User::factory()->create();
    Reservation::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->reservations())->toBeInstanceOf(HasMany::class);
    expect($user->reservations)->toHaveCount(2);
});

test('has many loans', function (): void {
    $user = User::factory()->create();
    Loan::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->loans())->toBeInstanceOf(HasMany::class);
    expect($user->loans)->toHaveCount(2);
});

test('has many fines', function (): void {
    $user = User::factory()->create();
    Fine::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->fines())->toBeInstanceOf(HasMany::class);
    expect($user->fines)->toHaveCount(2);
});

test('pending_fines_total returns correct sum of pending and partially_paid fines', function (): void {
    $user = User::factory()->create();

    Fine::factory()->pending()->create(['user_id' => $user->id, 'amount' => 30.0]);
    Fine::factory()->partiallyPaid()->create(['user_id' => $user->id, 'amount' => 20.0]);

    expect($user->pending_fines_total)->toBe(40.0);
});

test('pending_fines_total excludes paid and waived fines', function (): void {
    $user = User::factory()->create();

    Fine::factory()->pending()->create(['user_id' => $user->id, 'amount' => 15.0]);
    Fine::factory()->paid()->create(['user_id' => $user->id, 'amount' => 45.0]);
    Fine::factory()->waived()->create(['user_id' => $user->id, 'amount' => 55.0]);

    expect($user->pending_fines_total)->toBe(15.0);
});

test('pending_fines_total is zero when the user has no fines', function (): void {
    expect(User::factory()->create()->pending_fines_total)->toBe(0.0);
});

test('pending_fines_total ignores other users fines', function (): void {
    $user = User::factory()->create();
    Fine::factory()->pending()->create(['amount' => 99.0]);

    expect($user->pending_fines_total)->toBe(0.0);
});
