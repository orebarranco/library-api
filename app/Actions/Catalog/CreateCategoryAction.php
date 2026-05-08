<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\DTOs\Catalog\CreateCategoryDTO;
use App\Models\Category;

final class CreateCategoryAction
{
    public function execute(CreateCategoryDTO $data): Category
    {
        return Category::query()->create([
            'name' => $data->name,
            'description' => $data->description,
        ]);
    }
}
