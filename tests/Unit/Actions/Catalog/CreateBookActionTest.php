<?php

declare(strict_types=1);

use App\Actions\Catalog\CreateBookAction;
use App\DTOs\Catalog\CreateBookDTO;
use App\Exceptions\Catalog\DuplicateIsbnException;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;

beforeEach(function (): void {
    $this->action = new CreateBookAction();
});

it('creates book with correct data', function (): void {
    $dto = new CreateBookDTO(
        title: 'Clean Code',
        isbn: '9780132350884',
        publication_year: 2008,
        book_value: '29.99',
        author_id: Author::factory()->create()->id,
        category_id: Category::factory()->create()->id,
    );

    $result = $this->action->execute($dto);

    expect($result)->toBeInstanceOf(Book::class)
        ->and($result->exists)->toBeTrue()
        ->and($result->title)->toBe('Clean Code')
        ->and($result->isbn)->toBe('9780132350884');

    $this->assertDatabaseHas('books', [
        'title' => 'Clean Code',
        'isbn' => '9780132350884',
    ]);
});

it('throws DuplicateIsbnException when isbn already exists', function (): void {
    $author = Author::factory()->create();
    $category = Category::factory()->create();

    Book::factory()->create(['isbn' => '9780132350884']);

    $dto = new CreateBookDTO(
        title: 'Another Clean Code',
        isbn: '9780132350884',
        publication_year: 2008,
        book_value: '29.99',
        author_id: $author->id,
        category_id: $category->id,
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(DuplicateIsbnException::class);
});
