<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginUserDTO;
use App\Enums\UserStatus;
use App\Exceptions\Auth\AccountSuspendedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class LoginUserAction
{
    /**
     * @return array{user: User, token: string}
     *
     * @throws InvalidCredentialsException
     * @throws AccountSuspendedException
     */
    public function execute(LoginUserDTO $data): array
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if ($user->status === UserStatus::Suspended) {
            throw new AccountSuspendedException();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
