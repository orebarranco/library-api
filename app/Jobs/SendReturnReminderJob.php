<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Notifications\ReturnReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendReturnReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $days,
    ) {}

    public function handle(): void
    {
        $target = now()->addDays($this->days);

        $due = Loan::query()
            ->with(['user', 'bookCopy.book'])
            ->where('status', LoanStatus::Active)
            ->whereBetween('due_date', [$target->clone()->startOfDay(), $target->clone()->endOfDay()])
            ->get();

        foreach ($due as $loan) {
            $loan->user->notify(new ReturnReminderNotification($loan, $this->days));
        }
    }
}
