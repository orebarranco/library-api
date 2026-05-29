<?php

declare(strict_types=1);

use App\Actions\User\SuspendUserAction;
use App\Actions\User\UnsuspendUserAction;
use App\Enums\UserStatus;
use App\Models\User;

it('sets user status to suspended', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    new SuspendUserAction()->execute($user);

    expect($user->fresh()->status)->toBe(UserStatus::Suspended);
});

it('sets user status back to active on unsuspend', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Suspended]);

    new UnsuspendUserAction()->execute($user);

    expect($user->fresh()->status)->toBe(UserStatus::Active);
});
