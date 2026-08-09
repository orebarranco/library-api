<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LoanStatus;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
final class LoanFactory extends Factory
{
    private const int LOAN_PERIOD_DAYS = 14;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $loanedAt = now();

        return [
            'user_id' => User::factory(),
            'book_copy_id' => BookCopy::factory(),
            'reservation_id' => Reservation::factory(),
            'loaned_at' => $loanedAt,
            'due_date' => $loanedAt->clone()->addDays(self::LOAN_PERIOD_DAYS),
            'renewal_count' => 0,
            'status' => LoanStatus::Active,
        ];
    }

    public function active(): static
    {
        $loanedAt = now();

        return $this->state([
            'status' => LoanStatus::Active,
            'loaned_at' => $loanedAt,
            'due_date' => $loanedAt->clone()->addDays(self::LOAN_PERIOD_DAYS),
            'returned_at' => null,
        ]);
    }

    public function overdue(): static
    {
        $loanedAt = now()->subDays(self::LOAN_PERIOD_DAYS + 5);

        return $this->state([
            'status' => LoanStatus::Overdue,
            'loaned_at' => $loanedAt,
            'due_date' => $loanedAt->clone()->addDays(self::LOAN_PERIOD_DAYS),
            'returned_at' => null,
        ]);
    }

    public function returned(): static
    {
        $loanedAt = now()->subDays(self::LOAN_PERIOD_DAYS);

        return $this->state([
            'status' => LoanStatus::Returned,
            'loaned_at' => $loanedAt,
            'due_date' => $loanedAt->clone()->addDays(self::LOAN_PERIOD_DAYS),
            'returned_at' => now(),
        ]);
    }
}
