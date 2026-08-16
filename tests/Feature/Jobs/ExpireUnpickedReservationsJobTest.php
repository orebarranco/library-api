<?php

declare(strict_types=1);

use App\Enums\ReservationStatus;
use App\Jobs\ExpireUnpickedReservationsJob;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationExpiredNotification;
use Illuminate\Support\Facades\Notification;

it('expires approved reservations past expires_at', function (): void {
    $reservation = Reservation::factory()->approved()->create(['expires_at' => now()->subHour()]);

    new ExpireUnpickedReservationsJob()->handle();

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Expired);
});

it('does not expire approved reservations not yet past expires_at', function (): void {
    $reservation = Reservation::factory()->approved()->create(['expires_at' => now()->addHours(5)]);

    new ExpireUnpickedReservationsJob()->handle();

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Approved);
});

it('does not affect non-approved reservations', function (): void {
    $pending = Reservation::factory()->pending()->create(['expires_at' => now()->subDay()]);
    $cancelled = Reservation::factory()->cancelled()->create(['expires_at' => now()->subDay()]);

    new ExpireUnpickedReservationsJob()->handle();

    expect($pending->refresh()->status)->toBe(ReservationStatus::Pending);
    expect($cancelled->refresh()->status)->toBe(ReservationStatus::Cancelled);
});

it('sends ReservationExpiredNotification for each expired reservation', function (): void {
    Notification::fake();

    $first = User::factory()->create();
    $second = User::factory()->create();

    Reservation::factory()->approved()->create([
        'user_id' => $first->id,
        'expires_at' => now()->subHour(),
    ]);
    Reservation::factory()->approved()->create([
        'user_id' => $second->id,
        'expires_at' => now()->subHour(),
    ]);

    new ExpireUnpickedReservationsJob()->handle();

    Notification::assertSentTo($first, ReservationExpiredNotification::class);
    Notification::assertSentTo($second, ReservationExpiredNotification::class);
});

it('job is scheduled at 20:00 daily', function (): void {
    expect(scheduledExpressions(ExpireUnpickedReservationsJob::class))->toBe(['0 20 * * *']);
});
