<?php

declare(strict_types=1);

use App\DTOs\Fine\PayFineDTO;
use App\DTOs\Fine\WaiveFineDTO;

it('PayFineDTO keeps a positive amount', function (): void {
    expect(new PayFineDTO(12.5)->amount)->toBe(12.5);
});

it('PayFineDTO rejects a non-positive amount', function (float $amount): void {
    expect(fn (): PayFineDTO => new PayFineDTO($amount))
        ->toThrow(InvalidArgumentException::class);
})->with([0.0, -1.0]);

it('WaiveFineDTO keeps the reason', function (): void {
    expect(new WaiveFineDTO('Library error.')->reason)->toBe('Library error.');
});

it('WaiveFineDTO rejects a blank reason', function (string $reason): void {
    expect(fn (): WaiveFineDTO => new WaiveFineDTO($reason))
        ->toThrow(InvalidArgumentException::class);
})->with(['', '   ']);
