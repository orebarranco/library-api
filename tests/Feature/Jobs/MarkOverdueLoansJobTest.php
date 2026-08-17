<?php

declare(strict_types=1);

use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Jobs\MarkOverdueLoansJob;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\OverdueLoanAlertNotification;
use Illuminate\Support\Facades\Notification;

it('marks active loans past due_date as overdue', function (): void {
    $loan = Loan::factory()->active()->create(['due_date' => now()->subDay()]);

    new MarkOverdueLoansJob()->handle();

    expect($loan->refresh()->status)->toBe(LoanStatus::Overdue);
});

it('does not mark active loans with future due_date', function (): void {
    $loan = Loan::factory()->active()->create(['due_date' => now()->addDays(3)]);

    new MarkOverdueLoansJob()->handle();

    expect($loan->refresh()->status)->toBe(LoanStatus::Active);
});

it('does not affect already returned or overdue loans', function (): void {
    $returned = Loan::factory()->returned()->create(['due_date' => now()->subDays(20)]);
    $overdue = Loan::factory()->overdue()->create(['due_date' => now()->subDays(20)]);

    new MarkOverdueLoansJob()->handle();

    expect($returned->refresh()->status)->toBe(LoanStatus::Returned);
    expect($overdue->refresh()->status)->toBe(LoanStatus::Overdue);
    expect($returned->refresh()->returned_at)->not->toBeNull();
});

it('job is scheduled at 00:00 daily in routes/console.php', function (): void {
    expect(scheduledExpressions(MarkOverdueLoansJob::class))->toBe(['0 0 * * *']);
});

it('sends OverdueLoanAlertNotification to all admins when loan crosses 7-day overdue threshold', function (): void {
    Notification::fake();

    $admins = User::factory()->count(2)->create(['role' => UserRole::Admin]);
    Loan::factory()->active()->create(['due_date' => now()->subDays(7)->subHours(2)]);

    new MarkOverdueLoansJob()->handle();

    Notification::assertSentTo($admins, OverdueLoanAlertNotification::class);
});

it('does not send alert for loans overdue less than 7 days', function (): void {
    Notification::fake();

    User::factory()->create(['role' => UserRole::Admin]);
    Loan::factory()->active()->create(['due_date' => now()->subDays(3)]);

    new MarkOverdueLoansJob()->handle();

    Notification::assertNothingSent();
});

it('does not send duplicate alert if loan was already past 7 days on previous run', function (): void {
    Notification::fake();

    User::factory()->create(['role' => UserRole::Admin]);
    Loan::factory()->overdue()->create(['due_date' => now()->subDays(9)]);

    new MarkOverdueLoansJob()->handle();

    Notification::assertNothingSent();
});

it('does not notify when no admin exists', function (): void {
    Notification::fake();

    Loan::factory()->active()->create(['due_date' => now()->subDays(7)->subHours(2)]);

    new MarkOverdueLoansJob()->handle();

    Notification::assertNothingSent();
});
