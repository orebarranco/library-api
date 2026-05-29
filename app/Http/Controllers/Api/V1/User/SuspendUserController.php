<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\User\SuspendUserAction;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

final class SuspendUserController
{
    use ApiResponse;

    public function __invoke(User $user, SuspendUserAction $action): JsonResponse
    {
        $action->execute($user);

        return $this->success(new UserResource($user->refresh()));
    }
}
