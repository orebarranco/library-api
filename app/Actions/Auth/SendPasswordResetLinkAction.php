<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\ForgotPasswordDTO;
use Illuminate\Support\Facades\Password;

final class SendPasswordResetLinkAction
{
    public function execute(ForgotPasswordDTO $data): void
    {
        Password::sendResetLink(['email' => $data->email]);
    }
}
