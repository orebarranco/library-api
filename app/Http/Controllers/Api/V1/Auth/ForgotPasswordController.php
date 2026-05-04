<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\SendPasswordResetLinkAction;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ForgotPasswordController
{
    use ApiResponse;

    public function __invoke(ForgotPasswordRequest $request, SendPasswordResetLinkAction $action): JsonResponse
    {
        $action->execute($request->toDto());

        return $this->noData();
    }
}
