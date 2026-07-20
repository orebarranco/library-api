<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\DTOs\Catalog\CreateBookDTO;
use App\Exceptions\Catalog\DuplicateIsbnException;
use App\Models\Book;

final class CreateBookAction
{
    public function execute(CreateBookDTO $data): Book
    {
        if (Book::query()->where('isbn', $data->isbn)->exists()) {
            throw new DuplicateIsbnException($data->isbn);
        }

        return Book::query()->create([
            'title' => $data->title,
            'isbn' => $data->isbn,
            'description' => $data->description,
            'publication_year' => $data->publication_year,
            'publisher' => $data->publisher,
            'book_value' => $data->book_value,
            'author_id' => $data->author_id,
            'category_id' => $data->category_id,
        ]);
    }
}
