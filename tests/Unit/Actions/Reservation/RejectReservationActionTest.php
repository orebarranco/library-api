<?php

declare(strict_types=1);

use App\Actions\Reservation\RejectReservationAction;
use App\DTOs\Reservation\RejectReservationDTO;
use App\Enums\ReservationStatus;
use App\Exceptions\Reservation\ReservationNotPendingException;
use App\Models\Reservation;

beforeEach(function (): void {
    $this->action = new RejectReservationAction();
});

it('sets rejected status and persists reason', function (): void {
    $reservation = Reservation::factory()->pending()->create();
    $dto = new RejectReservationDTO(reason: 'Book needed for reference collection.');

    $result = $this->action->execute($reservation, $dto);

    expect($result->status)->toBe(ReservationStatus::Rejected)
        ->and($result->reason)->toBe('Book needed for reference collection.');

    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'status' => ReservationStatus::Rejected->value,
        'reason' => 'Book needed for reference collection.',
    ]);
});

it('throws ReservationNotPendingException when reservation is not pending', function (): void {
    $reservation = Reservation::factory()->approved()->create();
    $dto = new RejectReservationDTO(reason: 'Too late.');

    expect(fn () => $this->action->execute($reservation, $dto))
        ->toThrow(ReservationNotPendingException::class);
});
