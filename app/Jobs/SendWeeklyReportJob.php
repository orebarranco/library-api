<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\Report\WeeklyReportDTO;
use App\Enums\UserRole;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\WeeklyReportNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

final class SendWeeklyReportJob implements ShouldQueue
{
    use Queueable;

    private const int REPORT_WINDOW_DAYS = 7;

    public function handle(): void
    {
        $admins = User::query()->where('role', UserRole::Admin)->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new WeeklyReportNotification($this->compile()));
    }

    /**
     * Fines collected is derived from the amount_paid recorded on fines touched
     * during the window: without a payment ledger there is no finer-grained
     * record of when each instalment was received.
     */
    private function compile(): WeeklyReportDTO
    {
        $periodStart = now()->subDays(self::REPORT_WINDOW_DAYS);
        $periodEnd = now();

        return new WeeklyReportDTO(
            period_start: $periodStart,
            period_end: $periodEnd,
            loans_count: Loan::query()
                ->whereBetween('loaned_at', [$periodStart, $periodEnd])
                ->count(),
            returns_count: Loan::query()
                ->whereBetween('returned_at', [$periodStart, $periodEnd])
                ->count(),
            fines_generated: (float) Fine::query()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->sum('amount'),
            fines_collected: (float) Fine::query()
                ->whereBetween('updated_at', [$periodStart, $periodEnd])
                ->sum('amount_paid'),
        );
    }
}
