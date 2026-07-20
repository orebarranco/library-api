<?php

declare(strict_types=1);

use App\Contracts\FineChecker;
use App\Contracts\NullFineChecker;
use App\Models\User;

it('returns 0.0 pending fines total', function (): void {
    $checker = new NullFineChecker();
    $user = User::factory()->create();

    expect($checker)->toBeInstanceOf(FineChecker::class);
    expect($checker->pendingFinesTotal($user))->toBe(0.0);
});
