<?php

declare(strict_types=1);

use App\Contracts\FineGenerator;
use App\Contracts\NullFineGenerator;
use App\DTOs\Fine\GenerateFineDTO;
use App\Enums\FineType;

it('implements the FineGenerator contract', function (): void {
    expect(new NullFineGenerator())->toBeInstanceOf(FineGenerator::class);
});

it('accepts a fine without persisting anything until Module 9', function (): void {
    $generator = new NullFineGenerator();

    $generator->generate(new GenerateFineDTO(
        user_id: 'user-123',
        type: FineType::LateReturn,
        amount: 12.5,
        description: 'Late return: 6 day(s) overdue.',
        loan_id: 'loan-123',
    ));

    expect(true)->toBeTrue();
});
