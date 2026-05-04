<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

final class LoginController
{
    use ApiResponse;

    public function __invoke(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $result = $action->execute($request->toDto());

        return $this->success(
            resource: new UserResource($result['user']),
            meta: ['token' => $result['token']],
        );
    }
}
