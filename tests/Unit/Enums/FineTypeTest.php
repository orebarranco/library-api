<?php

declare(strict_types=1);

use App\Enums\FineType;

it('has the expected cases and values', function (): void {
    expect(FineType::LateReturn->value)->toBe('late_return');
    expect(FineType::Damage->value)->toBe('damage');
    expect(FineType::Loss->value)->toBe('loss');
    expect(FineType::cases())->toHaveCount(3);
});
