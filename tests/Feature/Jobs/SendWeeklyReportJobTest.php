<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Jobs\SendWeeklyReportJob;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\WeeklyReportNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

it('sends email to all admin users', function (): void {
    $secondAdmin = User::factory()->create(['role' => UserRole::Admin]);

    new SendWeeklyReportJob()->handle();

    Notification::assertSentTo([$this->admin, $secondAdmin], WeeklyReportNotification::class);
});

it('does not send to librarian or user roles', function (): void {
    $librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $member = User::factory()->create(['role' => UserRole::User]);

    new SendWeeklyReportJob()->handle();

    Notification::assertNotSentTo($librarian, WeeklyReportNotification::class);
    Notification::assertNotSentTo($member, WeeklyReportNotification::class);
});

it('does nothing when there is no admin to report to', function (): void {
    $this->admin->forceDelete();

    new SendWeeklyReportJob()->handle();

    Notification::assertNothingSent();
});

it('email contains weekly loan count, return count, fines generated and fines collected', function (): void {
    Loan::factory()->count(2)->active()->create(['loaned_at' => now()->subDays(2)]);
    Loan::factory()->returned()->create([
        'loaned_at' => now()->subDays(3),
        'returned_at' => now()->subDay(),
    ]);
    // Detached from any loan so the factory does not add loans to the window.
    Fine::factory()->create(['amount' => 30.0, 'loan_id' => null]);
    Fine::factory()->paid()->create(['amount' => 12.0, 'loan_id' => null]);

    new SendWeeklyReportJob()->handle();

    Notification::assertSentTo(
        $this->admin,
        WeeklyReportNotification::class,
        function (WeeklyReportNotification $notification): bool {
            $body = implode(' ', $notification->toMail($this->admin)->introLines);

            return str_contains($body, 'Loans: 3')
                && str_contains($body, 'Returns: 1')
                && str_contains($body, 'Fines generated: 42.00')
                && str_contains($body, 'Fines collected: 12.00');
        },
    );
});

it('job is scheduled on monday at 08:00', function (): void {
    expect(scheduledExpressions(SendWeeklyReportJob::class))->toBe(['0 8 * * 1']);
});
