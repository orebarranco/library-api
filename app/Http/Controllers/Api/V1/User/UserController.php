<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\User\CreateUserAction;
use App\Actions\User\UpdateUserAction;
use App\Http\Requests\Api\V1\User\CreateUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

final class UserController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
            )
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('-created_at')
            ->paginate()
            ->appends(request()->query());

        return $this->successCollection(UserResource::collection($users));
    }

    public function store(CreateUserRequest $request, CreateUserAction $action): JsonResponse
    {
        $user = $action->execute($request->toDto());

        return $this->success(new UserResource($user), Response::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        return $this->success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): JsonResponse
    {
        $user = $action->execute($user, $request->toDto());

        return $this->success(new UserResource($user));
    }

    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }
}
