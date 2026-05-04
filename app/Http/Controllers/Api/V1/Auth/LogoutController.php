<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LogoutAction;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LogoutController
{
    public function __invoke(Request $request, LogoutAction $logoutAction): Response
    {
        /** @var User $user */
        $user = $request->user();

        $logoutAction->execute($user);

        return response()->noContent();
    }
}
