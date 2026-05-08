<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\CreateCategoryAction;
use App\Http\Requests\Api\V1\Catalog\CreateCategoryRequest;
use App\Http\Resources\Api\V1\Catalog\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CategoryController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->orderBy('name')
            ->paginate();

        return $this->successCollection(CategoryResource::collection($categories));
    }

    public function store(CreateCategoryRequest $request, CreateCategoryAction $action): JsonResponse
    {
        $category = $action->execute($request->toDto());

        return $this->success(new CategoryResource($category), Response::HTTP_CREATED);
    }
}
