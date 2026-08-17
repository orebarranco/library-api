<?php

declare(strict_types=1);

use App\Enums\ReportPeriod;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    Date::setTestNow('2026-08-17 12:00:00');
});

it('exposes the four report periods', function (): void {
    expect(array_map(fn (ReportPeriod $period): string => $period->value, ReportPeriod::cases()))
        ->toBe(['7days', '30days', '90days', '1year']);
});

it('resolves each period to the start of its window', function (ReportPeriod $period, string $expected): void {
    expect($period->since()->toDateTimeString())->toBe($expected);
})->with([
    [ReportPeriod::SevenDays, '2026-08-10 12:00:00'],
    [ReportPeriod::ThirtyDays, '2026-07-18 12:00:00'],
    [ReportPeriod::NinetyDays, '2026-05-19 12:00:00'],
    [ReportPeriod::OneYear, '2025-08-17 12:00:00'],
]);
