<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FineStatus;
use App\Enums\FineType;
use App\Enums\UserRole;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fine>
 */
final class FineFactory extends Factory
{
    private const float DEFAULT_AMOUNT = 10.0;

    /**
     * Amounts are fixed rather than random so that threshold assertions around
     * the $50 reservation block and the $100 suspension stay deterministic.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'loan_id' => Loan::factory(),
            'type' => FineType::LateReturn,
            'amount' => self::DEFAULT_AMOUNT,
            'amount_paid' => 0.0,
            'status' => FineStatus::Pending,
            'description' => 'Late return: 5 day(s) overdue.',
        ];
    }

    public function lateReturn(): static
    {
        return $this->state([
            'type' => FineType::LateReturn,
            'description' => 'Late return: 5 day(s) overdue.',
        ]);
    }

    public function damage(): static
    {
        return $this->state([
            'type' => FineType::Damage,
            'description' => 'Copy returned damaged.',
        ]);
    }

    public function loss(): static
    {
        return $this->state([
            'type' => FineType::Loss,
            'description' => 'Copy reported lost.',
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => FineStatus::Pending,
            'amount_paid' => 0.0,
        ]);
    }

    /**
     * The paid amount is derived in `afterMaking` rather than in the state
     * itself because attributes passed to `create()` are merged after every
     * state, so a state closure would only ever see the default amount.
     */
    public function partiallyPaid(): static
    {
        return $this->state(['status' => FineStatus::PartiallyPaid])
            ->afterMaking(function (Fine $fine): void {
                $fine->amount_paid = round($fine->amount / 2, 2);
            });
    }

    public function paid(): static
    {
        return $this->state(['status' => FineStatus::Paid])
            ->afterMaking(function (Fine $fine): void {
                $fine->amount_paid = $fine->amount;
            });
    }

    public function waived(): static
    {
        return $this->state([
            'status' => FineStatus::Waived,
            'amount_paid' => 0.0,
            'waived_by' => User::factory()->state(['role' => UserRole::Librarian]),
            'waived_reason' => 'Waived as a goodwill gesture.',
        ]);
    }
}
