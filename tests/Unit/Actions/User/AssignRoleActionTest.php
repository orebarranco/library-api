<?php

declare(strict_types=1);

use App\Actions\User\AssignRoleAction;
use App\DTOs\User\AssignRoleDTO;
use App\Enums\UserRole;
use App\Models\User;

// Every audited action records who performed it, so these unit tests act as a
// user the same way the HTTP routes behind them always do.
beforeEach(function (): void {
    test()->actingAs(User::factory()->create());
});

it('assigns correct role to user', function (UserRole $role): void {
    $user = User::factory()->create(['role' => UserRole::User]);

    new AssignRoleAction()->execute($user, new AssignRoleDTO(role: $role));

    expect($user->fresh()->role)->toBe($role);
})->with([
    'librarian' => UserRole::Librarian,
    'admin' => UserRole::Admin,
    'user' => UserRole::User,
]);
