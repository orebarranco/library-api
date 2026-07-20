<?php

declare(strict_types=1);

namespace App\DTOs\Catalog;

final readonly class UpdateBookDTO
{
    public function __construct(
        public ?string $title,
        public ?string $isbn,
        public ?string $description,
        public ?int $publication_year,
        public ?string $publisher,
        public ?string $book_value,
        public ?string $author_id,
        public ?string $category_id,
    ) {}
}
