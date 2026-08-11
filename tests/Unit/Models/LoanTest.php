<?php

declare(strict_types=1);

use App\Enums\LoanStatus;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

test('belongs to user', function (): void {
    $loan = Loan::factory()->create();

    expect($loan->user())->toBeInstanceOf(BelongsTo::class);
    expect($loan->user)->toBeInstanceOf(User::class);
});

test('belongs to book copy', function (): void {
    $loan = Loan::factory()->create();

    expect($loan->bookCopy())->toBeInstanceOf(BelongsTo::class);
    expect($loan->bookCopy)->toBeInstanceOf(BookCopy::class);
});

test('belongs to reservation', function (): void {
    $loan = Loan::factory()->create();

    expect($loan->reservation())->toBeInstanceOf(BelongsTo::class);
    expect($loan->reservation)->toBeInstanceOf(Reservation::class);
});

test('has many fines', function (): void {
    $loan = Loan::factory()->create();
    Fine::factory()->count(2)->create(['loan_id' => $loan->id]);

    expect($loan->fines())->toBeInstanceOf(HasMany::class);
    expect($loan->fines)->toHaveCount(2);
});

test('casts status to LoanStatus enum', function (): void {
    $loan = Loan::factory()->create();

    expect($loan->status)->toBeInstanceOf(LoanStatus::class);
});

test('casts renewal_count to integer', function (): void {
    $loan = Loan::factory()->create(['renewal_count' => 2]);

    expect($loan->renewal_count)->toBeInt()->toBe(2);
});

test('uses soft deletes', function (): void {
    $loan = Loan::factory()->create();

    expect(in_array(SoftDeletes::class, class_uses_recursive($loan)))->toBeTrue();

    $loan->delete();

    expect(Loan::withTrashed()->find($loan->id))->not->toBeNull();
    expect(Loan::query()->find($loan->id))->toBeNull();
});

test('factory active state sets due_date 14 days ahead and no returned_at', function (): void {
    $loan = Loan::factory()->active()->create();

    expect($loan->status)->toBe(LoanStatus::Active);
    expect($loan->returned_at)->toBeNull();
    expect((int) $loan->loaned_at->diffInDays($loan->due_date, absolute: true))->toBe(14);
});

test('factory overdue state sets due_date in the past and status to overdue', function (): void {
    $loan = Loan::factory()->overdue()->create();

    expect($loan->status)->toBe(LoanStatus::Overdue);
    expect($loan->due_date->isPast())->toBeTrue();
    expect($loan->returned_at)->toBeNull();
});

test('factory returned state sets returned_at and status to returned', function (): void {
    $loan = Loan::factory()->returned()->create();

    expect($loan->status)->toBe(LoanStatus::Returned);
    expect($loan->returned_at)->not->toBeNull();
});

test('isOverdue is true for an open loan past its due date', function (): void {
    expect(Loan::factory()->overdue()->create()->isOverdue())->toBeTrue();
    expect(Loan::factory()->active()->create()->isOverdue())->toBeFalse();
});

test('isOverdue is false for a returned loan even if due date passed', function (): void {
    $loan = Loan::factory()->returned()->create(['due_date' => now()->subDays(5)]);

    expect($loan->isOverdue())->toBeFalse();
});

test('daysOverdue returns zero when returned on time', function (): void {
    $loan = Loan::factory()->create([
        'due_date' => now()->addDays(3),
        'returned_at' => now(),
    ]);

    expect($loan->days_overdue)->toBe(0);
});

test('daysOverdue counts days between due_date and returned_at', function (): void {
    $loan = Loan::factory()->create([
        'due_date' => now()->subDays(5),
        'returned_at' => now(),
    ]);

    expect($loan->days_overdue)->toBe(5);
});
