<?php

declare(strict_types=1);

namespace App\Notifications;

use App\DTOs\Report\WeeklyReportDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class WeeklyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly WeeklyReportDTO $report,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $period = "{$this->report->period_start->toDateString()} to {$this->report->period_end->toDateString()}";

        return new MailMessage()
            ->subject("Weekly library report: {$period}")
            ->greeting('Here is last week at the library.')
            ->line("Loans: {$this->report->loans_count}")
            ->line("Returns: {$this->report->returns_count}")
            ->line('Fines generated: '.number_format($this->report->fines_generated, 2))
            ->line('Fines collected: '.number_format($this->report->fines_collected, 2));
    }
}
