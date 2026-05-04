<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ResetPasswordAction;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ResetPasswordController
{
    use ApiResponse;

    public function __invoke(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $action->execute($request->toDto());

        return $this->noData();
    }
}
