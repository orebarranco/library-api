<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookCopy>
 */
final class BookCopyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'code' => fake()->unique()->regexify('[0-9]{6}-[0-9]{3}'),
            'status' => BookCopyStatus::Available,
            'acquisition_date' => fake()->optional()->date(),
        ];
    }

    public function available(): static
    {
        return $this->state(['status' => BookCopyStatus::Available]);
    }

    public function loaned(): static
    {
        return $this->state(['status' => BookCopyStatus::Loaned]);
    }

    public function maintenance(): static
    {
        return $this->state(['status' => BookCopyStatus::Maintenance]);
    }

    public function lost(): static
    {
        return $this->state(['status' => BookCopyStatus::Lost]);
    }
}
