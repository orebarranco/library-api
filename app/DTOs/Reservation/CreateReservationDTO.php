<?php

declare(strict_types=1);

namespace App\DTOs\Reservation;

final readonly class CreateReservationDTO
{
    public function __construct(
        public string $book_id,
    ) {}
}
