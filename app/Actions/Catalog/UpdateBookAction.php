<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\DTOs\Catalog\UpdateBookDTO;
use App\Exceptions\Catalog\DuplicateIsbnException;
use App\Models\Book;

final class UpdateBookAction
{
    public function execute(Book $book, UpdateBookDTO $data): Book
    {
        if ($data->isbn !== null && $data->isbn !== $book->isbn) {
            if (Book::query()->where('isbn', $data->isbn)->exists()) {
                throw new DuplicateIsbnException($data->isbn);
            }
        }

        $attributes = array_filter([
            'title' => $data->title,
            'isbn' => $data->isbn,
            'publication_year' => $data->publication_year,
            'book_value' => $data->book_value,
            'author_id' => $data->author_id,
            'category_id' => $data->category_id,
        ], fn ($value) => $value !== null);

        $book->update($attributes);

        return $book->refresh();
    }
}
