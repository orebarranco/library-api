<?php

declare(strict_types=1);

use App\Models\User;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toContain('id', 'name', 'email', 'email_verified_at', 'role', 'status',
            'created_at', 'updated_at', 'deleted_at')
        ->toHaveCount(9);
});
