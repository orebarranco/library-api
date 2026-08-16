<?php

declare(strict_types=1);

use App\Actions\Fine\GenerateFineAction;
use App\Enums\FineType;
use App\Jobs\GenerateOverdueFinesJob;
use App\Models\Fine;
use App\Models\Loan;

function runGenerateOverdueFinesJob(): void
{
    new GenerateOverdueFinesJob()->handle(resolve(GenerateFineAction::class));
}

it('generates late_return fine for returned overdue loan with no existing fine', function (): void {
    $loan = Loan::factory()->returned()->create([
        'due_date' => now()->subDays(10),
        'returned_at' => now(),
    ]);

    runGenerateOverdueFinesJob();

    $this->assertDatabaseHas('fines', [
        'loan_id' => $loan->id,
        'user_id' => $loan->user_id,
        'type' => FineType::LateReturn->value,
    ]);
});

it('does not generate duplicate fine if late_return fine already exists', function (): void {
    $loan = Loan::factory()->returned()->create([
        'due_date' => now()->subDays(10),
        'returned_at' => now(),
    ]);
    Fine::factory()->lateReturn()->create([
        'loan_id' => $loan->id,
        'user_id' => $loan->user_id,
    ]);

    runGenerateOverdueFinesJob();

    expect(Fine::query()->where('loan_id', $loan->id)->count())->toBe(1);
});

it('does not generate fine for on-time returned loan', function (): void {
    Loan::factory()->returned()->create([
        'due_date' => now()->addDay(),
        'returned_at' => now(),
    ]);

    runGenerateOverdueFinesJob();

    expect(Fine::query()->count())->toBe(0);
});

it('fine amount is overdue_days times 2.00', function (): void {
    $loan = Loan::factory()->returned()->create([
        'due_date' => now()->subDays(10),
        'returned_at' => now(),
    ]);

    runGenerateOverdueFinesJob();

    expect(Fine::query()->where('loan_id', $loan->id)->sole()->amount)->toBe(20.0);
});

it('fine amount is capped at 60.00', function (): void {
    $loan = Loan::factory()->returned()->create([
        'due_date' => now()->subDays(45),
        'returned_at' => now(),
    ]);

    runGenerateOverdueFinesJob();

    expect(Fine::query()->where('loan_id', $loan->id)->sole()->amount)->toBe(60.0);
});

it('job is scheduled at 06:00 daily', function (): void {
    expect(scheduledExpressions(GenerateOverdueFinesJob::class))->toBe(['0 6 * * *']);
});
