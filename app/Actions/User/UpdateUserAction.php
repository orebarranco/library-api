<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTOs\User\UpdateUserDTO;
use App\Models\User;

final class UpdateUserAction
{
    public function execute(User $user, UpdateUserDTO $data): User
    {
        $user->update([
            'name' => $data->name,
            'email' => $data->email,
        ]);

        return $user;
    }
}
