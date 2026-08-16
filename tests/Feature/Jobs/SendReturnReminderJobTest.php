<?php

declare(strict_types=1);

use App\Jobs\SendReturnReminderJob;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\ReturnReminderNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    $this->borrower = User::factory()->create();
});

it('sends ReturnReminderNotification to users with loans due in specified days', function (): void {
    Loan::factory()->active()->create([
        'user_id' => $this->borrower->id,
        'due_date' => now()->addDays(3)->setTime(10, 0),
    ]);

    new SendReturnReminderJob(days: 3)->handle();

    Notification::assertSentTo($this->borrower, ReturnReminderNotification::class);
});

it('does not send to users with loans due on other days', function (): void {
    Loan::factory()->active()->create([
        'user_id' => $this->borrower->id,
        'due_date' => now()->addDays(5),
    ]);

    new SendReturnReminderJob(days: 3)->handle();

    Notification::assertNothingSent();
});

it('does not send to users with already returned loans', function (): void {
    Loan::factory()->returned()->create([
        'user_id' => $this->borrower->id,
        'due_date' => now()->addDays(3)->setTime(10, 0),
    ]);

    new SendReturnReminderJob(days: 3)->handle();

    Notification::assertNothingSent();
});

it('notification is sent via mail and database channels with the reminder window', function (): void {
    Loan::factory()->active()->create([
        'user_id' => $this->borrower->id,
        'due_date' => now()->addDay()->setTime(10, 0),
    ]);

    new SendReturnReminderJob(days: 1)->handle();

    Notification::assertSentTo(
        $this->borrower,
        ReturnReminderNotification::class,
        fn (ReturnReminderNotification $notification): bool => $notification->days === 1
            && $notification->via($this->borrower) === ['mail', 'database'],
    );
});

it('3-day instance is scheduled at 09:00 daily', function (): void {
    expect(scheduledExpressions(SendReturnReminderJob::class))->toContain('0 9 * * *');
});

it('1-day instance is scheduled at 18:00 daily', function (): void {
    expect(scheduledExpressions(SendReturnReminderJob::class))->toContain('0 18 * * *');
});
