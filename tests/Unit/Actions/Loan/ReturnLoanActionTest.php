<?php

declare(strict_types=1);

use App\Actions\Loan\ReturnLoanAction;
use App\Contracts\FineGenerator;
use App\DTOs\Fine\GenerateFineDTO;
use App\DTOs\Loan\ReturnLoanDTO;
use App\Enums\BookCopyStatus;
use App\Enums\FineType;
use App\Enums\LoanStatus;
use App\Exceptions\Loan\LoanAlreadyReturnedException;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;

/**
 * Captures the fines requested during a return so amounts and types can be
 * asserted without Module 9's persistence.
 */
function recordingFineGenerator(): FineGenerator
{
    return new class implements FineGenerator
    {
        /** @var list<GenerateFineDTO> */
        public array $generated = [];

        public function generate(GenerateFineDTO $dto): void
        {
            $this->generated[] = $dto;
        }
    };
}

// Every audited action records who performed it, so these unit tests act as a
// user the same way the HTTP routes behind them always do.
beforeEach(function (): void {
    test()->actingAs(User::factory()->create());

    $this->generator = recordingFineGenerator();
    $this->action = new ReturnLoanAction($this->generator);
    $this->copy = BookCopy::factory()->create(['status' => BookCopyStatus::Loaned]);
    $this->loan = Loan::factory()->active()->create(['book_copy_id' => $this->copy->id]);
});

it('marks the loan returned and frees the copy', function (): void {
    $result = $this->action->execute($this->loan, new ReturnLoanDTO());

    expect($result->status)->toBe(LoanStatus::Returned);
    expect($result->returned_at)->not->toBeNull();
    expect($this->copy->refresh()->status)->toBe(BookCopyStatus::Available);
});

it('throws LoanAlreadyReturnedException for an already returned loan', function (): void {
    $loan = Loan::factory()->returned()->create(['book_copy_id' => $this->copy->id]);

    expect(fn () => $this->action->execute($loan, new ReturnLoanDTO()))
        ->toThrow(LoanAlreadyReturnedException::class);
});

it('generates no fine when returned on time', function (): void {
    $this->action->execute($this->loan, new ReturnLoanDTO());

    expect($this->generator->generated)->toBeEmpty();
});

it('calculates overdue days correctly', function (): void {
    $this->loan->update(['due_date' => now()->subDays(7)]);

    $this->action->execute($this->loan, new ReturnLoanDTO());

    expect($this->generator->generated[0]->amount)->toBe(14.0);
    expect($this->generator->generated[0]->description)->toContain('7');
});

it('caps the fine amount at 60.00 for 30 or more overdue days', function (int $overdueDays): void {
    $this->loan->update(['due_date' => now()->subDays($overdueDays)]);

    $this->action->execute($this->loan, new ReturnLoanDTO());

    expect($this->generator->generated[0]->amount)->toBe(60.0);
})->with([30, 45, 100]);

it('sets the copy to maintenance and charges the assessed damage amount', function (): void {
    $this->action->execute($this->loan, new ReturnLoanDTO(damaged: true, damage_amount: 25.0));

    expect($this->copy->refresh()->status)->toBe(BookCopyStatus::Maintenance);
    expect($this->generator->generated)->toHaveCount(1);
    expect($this->generator->generated[0]->type)->toBe(FineType::Damage);
    expect($this->generator->generated[0]->amount)->toBe(25.0);
});

it('links every generated fine to the loan and its borrower', function (): void {
    $this->loan->update(['due_date' => now()->subDays(2)]);

    $this->action->execute($this->loan, new ReturnLoanDTO(damaged: true, damage_amount: 10.0));

    foreach ($this->generator->generated as $dto) {
        expect($dto->loan_id)->toBe($this->loan->id);
        expect($dto->user_id)->toBe($this->loan->user_id);
    }
});
