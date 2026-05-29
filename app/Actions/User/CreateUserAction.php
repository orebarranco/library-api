<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTOs\User\CreateUserDTO;
use App\Enums\UserStatus;
use App\Models\User;

final class CreateUserAction
{
    public function execute(CreateUserDTO $data): User
    {
        return User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
            'role' => $data->role,
            'status' => UserStatus::Active,
        ]);
    }
}
