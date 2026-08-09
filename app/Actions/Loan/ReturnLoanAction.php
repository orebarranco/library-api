<?php

declare(strict_types=1);

namespace App\Actions\Loan;

use App\Contracts\FineGenerator;
use App\DTOs\Fine\GenerateFineDTO;
use App\DTOs\Loan\ReturnLoanDTO;
use App\Enums\BookCopyStatus;
use App\Enums\FineType;
use App\Enums\LoanStatus;
use App\Exceptions\Loan\LoanAlreadyReturnedException;
use App\Models\Loan;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final readonly class ReturnLoanAction
{
    private const float LATE_FEE_PER_DAY = 2.0;

    private const float LATE_FEE_CAP = 60.0;

    public function __construct(
        private FineGenerator $fineGenerator,
    ) {}

    public function execute(Loan $loan, ReturnLoanDTO $dto): Loan
    {
        if (! $loan->status->isOpen()) {
            throw new LoanAlreadyReturnedException($loan->id);
        }

        // Strict mode forbids lazy loading, so the copy is fetched before the transaction.
        $loan->loadMissing('bookCopy');

        $returnedAt = now();
        $overdueDays = $this->overdueDays($loan, $returnedAt);

        DB::transaction(function () use ($loan, $dto, $returnedAt, $overdueDays): void {
            $loan->update([
                'returned_at' => $returnedAt,
                'status' => LoanStatus::Returned,
            ]);

            $loan->bookCopy->update([
                'status' => $dto->damaged ? BookCopyStatus::Maintenance : BookCopyStatus::Available,
            ]);

            if ($overdueDays > 0) {
                $this->generateLateReturnFine($loan, $overdueDays);
            }

            if ($dto->damaged && $dto->damage_amount !== null) {
                $this->generateDamageFine($loan, $dto->damage_amount);
            }
        });

        return $loan;
    }

    private function overdueDays(Loan $loan, CarbonInterface $returnedAt): int
    {
        if ($returnedAt->lessThanOrEqualTo($loan->due_date)) {
            return 0;
        }

        return (int) $loan->due_date->diffInDays($returnedAt, absolute: true);
    }

    /**
     * Late fees accrue at $2.00 per overdue day and are capped at $60.00 (30 days).
     */
    private function generateLateReturnFine(Loan $loan, int $overdueDays): void
    {
        $amount = min($overdueDays * self::LATE_FEE_PER_DAY, self::LATE_FEE_CAP);

        $this->fineGenerator->generate(new GenerateFineDTO(
            user_id: $loan->user_id,
            type: FineType::LateReturn,
            amount: $amount,
            description: "Late return: {$overdueDays} day(s) overdue.",
            loan_id: $loan->id,
        ));
    }

    private function generateDamageFine(Loan $loan, float $damageAmount): void
    {
        $this->fineGenerator->generate(new GenerateFineDTO(
            user_id: $loan->user_id,
            type: FineType::Damage,
            amount: $damageAmount,
            description: 'Copy returned damaged.',
            loan_id: $loan->id,
        ));
    }
}
