<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function something(): void
{
    // ..
}

/**
 * Cron expressions registered in routes/console.php for a scheduled job class.
 *
 * @param  class-string  $jobClass
 * @return list<string>
 */
function scheduledExpressions(string $jobClass): array
{
    return collect(resolve(Schedule::class)->events())
        ->filter(fn (object $event): bool => str_contains((string) $event->description, $jobClass))
        ->map(fn (object $event): string => (string) $event->expression)
        ->values()
        ->all();
}
