<?php

declare(strict_types=1);

use App\Actions\User\AssignRoleAction;
use App\DTOs\User\AssignRoleDTO;
use App\Enums\UserRole;
use App\Models\User;

it('assigns correct role to user', function (UserRole $role): void {
    $user = User::factory()->create(['role' => UserRole::User]);

    new AssignRoleAction()->execute($user, new AssignRoleDTO(role: $role));

    expect($user->fresh()->role)->toBe($role);
})->with([
    'librarian' => UserRole::Librarian,
    'admin' => UserRole::Admin,
    'user' => UserRole::User,
]);
