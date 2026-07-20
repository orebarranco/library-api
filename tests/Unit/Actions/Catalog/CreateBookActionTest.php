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
        description: 'A handbook of agile software craftsmanship.',
        publication_year: 2008,
        publisher: 'Prentice Hall',
        book_value: '29.99',
        author_id: Author::factory()->create()->id,
        category_id: Category::factory()->create()->id,
    );

    $result = $this->action->execute($dto);

    expect($result)->toBeInstanceOf(Book::class)
        ->and($result->exists)->toBeTrue()
        ->and($result->title)->toBe('Clean Code')
        ->and($result->isbn)->toBe('9780132350884')
        ->and($result->description)->toBe('A handbook of agile software craftsmanship.')
        ->and($result->publisher)->toBe('Prentice Hall');

    $this->assertDatabaseHas('books', [
        'title' => 'Clean Code',
        'isbn' => '9780132350884',
        'publisher' => 'Prentice Hall',
    ]);
});

it('creates book with null publisher', function (): void {
    $dto = new CreateBookDTO(
        title: 'Self-Published Book',
        isbn: '9780132350885',
        description: 'A book with no publisher on record.',
        publication_year: 2020,
        publisher: null,
        book_value: '15.00',
        author_id: Author::factory()->create()->id,
        category_id: Category::factory()->create()->id,
    );

    $result = $this->action->execute($dto);

    expect($result->publisher)->toBeNull();
});

it('throws DuplicateIsbnException when isbn already exists', function (): void {
    $author = Author::factory()->create();
    $category = Category::factory()->create();

    Book::factory()->create(['isbn' => '9780132350884']);

    $dto = new CreateBookDTO(
        title: 'Another Clean Code',
        isbn: '9780132350884',
        description: 'Another description.',
        publication_year: 2008,
        publisher: null,
        book_value: '29.99',
        author_id: $author->id,
        category_id: $category->id,
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(DuplicateIsbnException::class);
});
