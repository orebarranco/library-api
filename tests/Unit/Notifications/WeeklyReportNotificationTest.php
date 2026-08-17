<?php

declare(strict_types=1);

use App\DTOs\Report\WeeklyReportDTO;
use App\Models\User;
use App\Notifications\WeeklyReportNotification;

beforeEach(function (): void {
    $this->report = new WeeklyReportDTO(
        period_start: now()->subDays(7),
        period_end: now(),
        loans_count: 12,
        returns_count: 9,
        fines_generated: 84.5,
        fines_collected: 30.0,
    );
    $this->notification = new WeeklyReportNotification($this->report);
});

it('is sent only via the mail channel', function (): void {
    expect($this->notification->via(new User()))->toBe(['mail']);
});

it('mail subject carries the reported period', function (): void {
    $mail = $this->notification->toMail(new User());

    expect($mail->subject)->toContain($this->report->period_start->toDateString());
    expect($mail->subject)->toContain($this->report->period_end->toDateString());
});

it('mail lists loans, returns, fines generated and fines collected', function (): void {
    $body = implode(' ', $this->notification->toMail(new User())->introLines);

    expect($body)->toContain('Loans: 12');
    expect($body)->toContain('Returns: 9');
    expect($body)->toContain('Fines generated: 84.50');
    expect($body)->toContain('Fines collected: 30.00');
});
