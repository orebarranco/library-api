<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class RegisterController
{
    use ApiResponse;

    public function __invoke(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $result = $action->execute($request->toDto());

        return $this->success(
            resource: new UserResource($result['user']),
            status: Response::HTTP_CREATED,
            meta: ['token' => $result['token']],
        );
    }
}
