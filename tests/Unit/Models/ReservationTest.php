<?php

declare(strict_types=1);

use App\Enums\ReservationStatus;
use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

test('belongs to user', function (): void {
    $reservation = Reservation::factory()->create();

    expect($reservation->user())->toBeInstanceOf(BelongsTo::class);
    expect($reservation->user)->toBeInstanceOf(User::class);
});

test('belongs to book', function (): void {
    $reservation = Reservation::factory()->create();

    expect($reservation->book())->toBeInstanceOf(BelongsTo::class);
    expect($reservation->book)->toBeInstanceOf(Book::class);
});

test('has approved_by relationship', function (): void {
    $librarian = User::factory()->create();
    $reservation = Reservation::factory()->approved()->create(['approved_by' => $librarian->id]);

    expect($reservation->approvedBy())->toBeInstanceOf(BelongsTo::class);
    expect($reservation->approvedBy)->toBeInstanceOf(User::class);
    expect($reservation->approvedBy->id)->toBe($librarian->id);
});

test('casts status to ReservationStatus enum', function (): void {
    $reservation = Reservation::factory()->create();

    expect($reservation->status)->toBeInstanceOf(ReservationStatus::class);
});

test('uses soft deletes', function (): void {
    $reservation = Reservation::factory()->create();

    expect(in_array(SoftDeletes::class, class_uses_recursive($reservation)))->toBeTrue();

    $reservation->delete();

    expect(Reservation::withTrashed()->find($reservation->id))->not->toBeNull();
    expect(Reservation::query()->find($reservation->id))->toBeNull();
});

test('factory approved state sets correct fields', function (): void {
    $reservation = Reservation::factory()->approved()->create();

    expect($reservation->status)->toBe(ReservationStatus::Approved);
    expect($reservation->approved_at)->not->toBeNull();
    expect($reservation->approved_by)->not->toBeNull();
    expect($reservation->expires_at)->not->toBeNull();
    expect($reservation->approved_at->diffInHours($reservation->expires_at))->toEqual(72);
});
