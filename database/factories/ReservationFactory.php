<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
final class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => ReservationStatus::Pending,
            'reserved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => ReservationStatus::Pending,
        ]);
    }

    public function approved(): static
    {
        $approvedAt = now();

        return $this->state([
            'status' => ReservationStatus::Approved,
            'approved_at' => $approvedAt,
            'approved_by' => User::factory(),
            'expires_at' => $approvedAt->clone()->addHours(72),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => ReservationStatus::Rejected,
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => ReservationStatus::Completed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => ReservationStatus::Cancelled,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => ReservationStatus::Expired,
        ]);
    }
}
