<?php

declare(strict_types=1);

use App\Jobs\ExpireUnpickedReservationsJob;
use App\Jobs\GenerateOverdueFinesJob;
use App\Jobs\MarkOverdueLoansJob;
use App\Jobs\SendReturnReminderJob;
use App\Jobs\SendWeeklyReportJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new MarkOverdueLoansJob())->dailyAt('00:00');
Schedule::job(new GenerateOverdueFinesJob())->dailyAt('06:00');
Schedule::job(new SendReturnReminderJob(days: 3))->dailyAt('09:00');
Schedule::job(new SendReturnReminderJob(days: 1))->dailyAt('18:00');
Schedule::job(new ExpireUnpickedReservationsJob())->dailyAt('20:00');
Schedule::job(new SendWeeklyReportJob())->weeklyOn(1, '08:00');
