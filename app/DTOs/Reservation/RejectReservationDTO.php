<?php

declare(strict_types=1);

namespace App\DTOs\Reservation;

final readonly class RejectReservationDTO
{
    public function __construct(
        public string $reason,
    ) {}
}
