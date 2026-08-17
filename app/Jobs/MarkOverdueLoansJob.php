<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\OverdueLoanAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

final class MarkOverdueLoansJob implements ShouldQueue
{
    use Queueable;

    /**
     * Days past the due date at which admins are alerted about a loan.
     */
    private const int ALERT_THRESHOLD_DAYS = 7;

    public function handle(): void
    {
        Loan::query()
            ->where('status', LoanStatus::Active)
            ->where('due_date', '<', now())
            ->update(['status' => LoanStatus::Overdue]);

        $this->alertAdmins();
    }

    /**
     * Only loans whose overdue count is exactly the threshold are alerted, so a
     * daily run reports each loan once instead of re-alerting every morning.
     */
    private function alertAdmins(): void
    {
        $crossing = Loan::query()
            ->with(['user', 'bookCopy.book'])
            ->where('status', LoanStatus::Overdue)
            ->where('due_date', '>', now()->subDays(self::ALERT_THRESHOLD_DAYS + 1))
            ->where('due_date', '<=', now()->subDays(self::ALERT_THRESHOLD_DAYS))
            ->get();

        if ($crossing->isEmpty()) {
            return;
        }

        $admins = User::query()->where('role', UserRole::Admin)->get();

        foreach ($crossing as $loan) {
            Notification::send($admins, new OverdueLoanAlertNotification($loan));
        }
    }
}
