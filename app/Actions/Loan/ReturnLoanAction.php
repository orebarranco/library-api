<?php

declare(strict_types=1);

namespace App\Actions\Loan;

use App\Contracts\FineGenerator;
use App\DTOs\Fine\GenerateFineDTO;
use App\DTOs\Loan\ReturnLoanDTO;
use App\Enums\BookCopyStatus;
use App\Enums\FineType;
use App\Enums\LoanStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\Loan\LoanAlreadyReturnedException;
use App\Models\Loan;
use App\Models\Reservation;
use App\Notifications\BookAvailableNotification;
use Illuminate\Support\Facades\DB;

final readonly class ReturnLoanAction
{
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

        // Read before the update: both accessors fall back to the present moment
        // while returned_at is still null, which is exactly the return instant.
        $returnedAt = now();
        $overdueDays = $loan->days_overdue;
        $lateFee = $loan->late_fee;

        DB::transaction(function () use ($loan, $dto, $returnedAt, $overdueDays, $lateFee): void {
            $loan->update([
                'returned_at' => $returnedAt,
                'status' => LoanStatus::Returned,
            ]);

            $loan->bookCopy->update([
                'status' => $dto->damaged ? BookCopyStatus::Maintenance : BookCopyStatus::Available,
            ]);

            if ($overdueDays > 0) {
                $this->generateLateReturnFine($loan, $overdueDays, $lateFee);
            }

            if ($dto->damaged && $dto->damage_amount !== null) {
                $this->generateDamageFine($loan, $dto->damage_amount);
            }
        });

        if (! $dto->damaged) {
            $this->notifyWaitingReservations($loan);
        }

        return $loan;
    }

    /**
     * The freed copy is offered to every member still holding an approved
     * reservation for the same book; loans consume their own reservation, so
     * the ones left in APPROVED are genuinely waiting.
     */
    private function notifyWaitingReservations(Loan $loan): void
    {
        $waiting = Reservation::query()
            ->with('user')
            ->where('book_id', $loan->bookCopy->book_id)
            ->where('status', ReservationStatus::Approved)
            ->get();

        foreach ($waiting as $reservation) {
            $reservation->user->notify(new BookAvailableNotification($reservation));
        }
    }

    private function generateLateReturnFine(Loan $loan, int $overdueDays, float $amount): void
    {
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
