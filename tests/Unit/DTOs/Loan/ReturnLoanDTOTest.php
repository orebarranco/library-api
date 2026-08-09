<?php

declare(strict_types=1);

use App\DTOs\Loan\ReturnLoanDTO;

it('defaults to an undamaged return with no amount', function (): void {
    $dto = new ReturnLoanDTO();

    expect($dto->damaged)->toBeFalse();
    expect($dto->damage_amount)->toBeNull();
});

it('carries the assessed damage amount', function (): void {
    $dto = new ReturnLoanDTO(damaged: true, damage_amount: 30.0);

    expect($dto->damaged)->toBeTrue();
    expect($dto->damage_amount)->toBe(30.0);
});

it('rejects a damaged return without an assessed amount', function (): void {
    expect(fn (): ReturnLoanDTO => new ReturnLoanDTO(damaged: true))
        ->toThrow(InvalidArgumentException::class);
});
