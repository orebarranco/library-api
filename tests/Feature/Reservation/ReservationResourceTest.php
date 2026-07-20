<?php

declare(strict_types=1);

use App\Http\Resources\Api\V1\Reservation\ReservationResource;
use App\Models\Reservation;

it('renders JSON:API format with the expected attributes', function (): void {
    $reservation = Reservation::factory()->approved()->create(['reason' => null]);

    $response = new ReservationResource($reservation)->response();
    $data = $response->getData(true)['data'];

    expect($data['type'])->toBe('reservations')
        ->and($data['id'])->toBe($reservation->id)
        ->and($data['attributes'])->toHaveKeys([
            'status', 'reserved_at', 'approved_at', 'approved_by', 'expires_at', 'reason', 'created_at',
        ])
        ->and($data['attributes']['status'])->toBe('approved');
});
