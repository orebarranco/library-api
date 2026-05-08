<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\CreateAuthorAction;
use App\Actions\Catalog\UpdateAuthorAction;
use App\Http\Requests\Api\V1\Catalog\CreateAuthorRequest;
use App\Http\Requests\Api\V1\Catalog\UpdateAuthorRequest;
use App\Http\Resources\Api\V1\Catalog\AuthorResource;
use App\Models\Author;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AuthorController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $authors = Author::query()
            ->orderBy('name')
            ->paginate();

        return $this->successCollection(AuthorResource::collection($authors));
    }

    public function store(CreateAuthorRequest $request, CreateAuthorAction $action): JsonResponse
    {
        $author = $action->execute($request->toDto());

        return $this->success(new AuthorResource($author), Response::HTTP_CREATED);
    }

    public function update(UpdateAuthorRequest $request, Author $author, UpdateAuthorAction $action): JsonResponse
    {
        $author = $action->execute($author, $request->toDto());

        return $this->success(new AuthorResource($author));
    }
}
