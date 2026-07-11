<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\DTOs\Catalog\ChangeBookCopyStatusDTO;
use App\Models\BookCopy;

final class ChangeBookCopyStatusAction
{
    public function execute(BookCopy $bookCopy, ChangeBookCopyStatusDTO $data): BookCopy
    {
        $bookCopy->update(['status' => $data->status]);

        return $bookCopy->refresh();
    }
}
