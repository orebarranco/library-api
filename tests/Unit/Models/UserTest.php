<?php

declare(strict_types=1);

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
