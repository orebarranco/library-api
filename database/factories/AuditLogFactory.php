<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
final class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => AuditAction::BookUpdated,
            'model_type' => class_basename(Book::class),
            'model_id' => (string) $this->faker->uuid(),
            'old_values' => ['title' => $this->faker->sentence(3)],
            'new_values' => ['title' => $this->faker->sentence(3)],
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
