<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Fine\GenerateFineAction;
use App\DTOs\Fine\GenerateFineDTO;
use App\Enums\FineType;
use App\Enums\LoanStatus;
use App\Models\Fine;
use App\Models\Loan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateOverdueFinesJob implements ShouldQueue
{
    use Queueable;

    public function handle(GenerateFineAction $generateFine): void
    {
        $unfined = Loan::query()
            ->where('status', LoanStatus::Returned)
            ->whereColumn('returned_at', '>', 'due_date')
            ->whereDoesntHave('fines', function (Builder $query): void {
                /** @var Builder<Fine> $query */
                $query->where('type', FineType::LateReturn);
            })
            ->get();

        foreach ($unfined as $loan) {
            $generateFine->execute(new GenerateFineDTO(
                user_id: $loan->user_id,
                type: FineType::LateReturn,
                amount: $loan->late_fee,
                description: "Late return: {$loan->days_overdue} day(s) overdue.",
                loan_id: $loan->id,
            ));
        }
    }
}
