<?php

declare(strict_types=1);

use App\Enums\FineStatus;
use App\Enums\FineType;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('belongs to user', function (): void {
    $fine = Fine::factory()->create();

    expect($fine->user())->toBeInstanceOf(BelongsTo::class);
    expect($fine->user)->toBeInstanceOf(User::class);
});

test('belongs to loan', function (): void {
    $fine = Fine::factory()->create();

    expect($fine->loan())->toBeInstanceOf(BelongsTo::class);
    expect($fine->loan)->toBeInstanceOf(Loan::class);
});

test('belongs to waived_by user', function (): void {
    $fine = Fine::factory()->waived()->create();

    expect($fine->waivedBy())->toBeInstanceOf(BelongsTo::class);
    expect($fine->waivedBy)->toBeInstanceOf(User::class);
});

test('casts status to FineStatus enum', function (): void {
    expect(Fine::factory()->create()->status)->toBeInstanceOf(FineStatus::class);
});

test('casts type to FineType enum', function (): void {
    expect(Fine::factory()->create()->type)->toBeInstanceOf(FineType::class);
});

test('casts amounts to float', function (): void {
    $fine = Fine::factory()->create(['amount' => 12.5, 'amount_paid' => 2.5]);

    expect($fine->refresh()->amount)->toBeFloat()->toBe(12.5);
    expect($fine->amount_paid)->toBeFloat()->toBe(2.5);
});

test('factory creates valid fine', function (): void {
    $fine = Fine::factory()->create();

    expect($fine->exists)->toBeTrue();
    expect($fine->user_id)->not->toBeNull();
    expect($fine->loan_id)->not->toBeNull();
    expect($fine->status)->toBe(FineStatus::Pending);
    expect($fine->type)->toBe(FineType::LateReturn);
    expect($fine->amount)->toBe(10.0);
    expect($fine->amount_paid)->toBe(0.0);
    expect($fine->description)->not->toBeEmpty();
});

test('a fine can exist without a loan', function (): void {
    $fine = Fine::factory()->create(['loan_id' => null]);

    expect($fine->loan_id)->toBeNull();
    expect($fine->loan)->toBeNull();
});

test('balance is the unpaid remainder', function (): void {
    $fine = Fine::factory()->create(['amount' => 40.0, 'amount_paid' => 15.5]);

    expect($fine->balance)->toBe(24.5);
});

test('factory type states set the matching type', function (string $state, FineType $type): void {
    expect(Fine::factory()->{$state}()->create()->type)->toBe($type);
})->with([
    ['lateReturn', FineType::LateReturn],
    ['damage', FineType::Damage],
    ['loss', FineType::Loss],
]);

test('factory pending state leaves the fine unpaid', function (): void {
    $fine = Fine::factory()->pending()->create(['amount' => 30.0]);

    expect($fine->status)->toBe(FineStatus::Pending);
    expect($fine->amount_paid)->toBe(0.0);
    expect($fine->balance)->toBe(30.0);
});

test('factory partially paid state pays half the amount', function (): void {
    $fine = Fine::factory()->partiallyPaid()->create(['amount' => 30.0]);

    expect($fine->status)->toBe(FineStatus::PartiallyPaid);
    expect($fine->amount_paid)->toBe(15.0);
});

test('factory paid state pays the amount in full', function (): void {
    $fine = Fine::factory()->paid()->create(['amount' => 30.0]);

    expect($fine->status)->toBe(FineStatus::Paid);
    expect($fine->amount_paid)->toBe(30.0);
    expect($fine->balance)->toBe(0.0);
});

test('factory waived state records who waived it and why', function (): void {
    $fine = Fine::factory()->waived()->create();

    expect($fine->status)->toBe(FineStatus::Waived);
    expect($fine->waived_by)->not->toBeNull();
    expect($fine->waived_reason)->not->toBeNull();
});
