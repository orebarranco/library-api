<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\DTOs\Catalog\UpdateAuthorDTO;
use App\Models\Author;

final class UpdateAuthorAction
{
    public function execute(Author $author, UpdateAuthorDTO $data): Author
    {
        $author->update([
            'name' => $data->name,
            'biography' => $data->biography,
            'birth_date' => $data->birth_date,
        ]);

        return $author;
    }
}
