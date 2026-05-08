<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\DTOs\Catalog\CreateAuthorDTO;
use App\Models\Author;

final class CreateAuthorAction
{
    public function execute(CreateAuthorDTO $data): Author
    {
        return Author::query()->create([
            'name' => $data->name,
            'biography' => $data->biography,
            'birth_date' => $data->birth_date,
        ]);
    }
}
