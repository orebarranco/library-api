<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\User\AssignRoleAction;
use App\Http\Requests\Api\V1\User\AssignRoleRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AssignRoleController
{
    use ApiResponse;

    public function __invoke(AssignRoleRequest $request, User $user, AssignRoleAction $action): JsonResponse
    {
        $user = $action->execute($user, $request->toDto());

        return $this->success(new UserResource($user));
    }
}
