<?php

declare(strict_types=1);

use App\Actions\Catalog\UpdateBookAction;
use App\DTOs\Catalog\UpdateBookDTO;
use App\Exceptions\Catalog\DuplicateIsbnException;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;

// Every audited action records who performed it, so these unit tests act as a
// user the same way the HTTP routes behind them always do.
beforeEach(function (): void {
    test()->actingAs(User::factory()->create());

    $this->action = new UpdateBookAction();
    $this->author = Author::factory()->create();
    $this->category = Category::factory()->create();
});

it('updates book with provided fields', function (): void {
    $book = Book::factory()->create([
        'title' => 'Original Title',
        'isbn' => '9780132350884',
    ]);

    $dto = new UpdateBookDTO(title: 'Updated Title', isbn: null, description: null, publication_year: null, publisher: null, book_value: null, author_id: null, category_id: null);

    $result = $this->action->execute($book, $dto);

    expect($result->title)->toBe('Updated Title')
        ->and($result->isbn)->toBe('9780132350884');
});

it('updates publisher when provided', function (): void {
    $book = Book::factory()->create(['publisher' => 'Old Publisher']);

    $dto = new UpdateBookDTO(title: null, isbn: null, description: null, publication_year: null, publisher: 'New Publisher', book_value: null, author_id: null, category_id: null);

    $result = $this->action->execute($book, $dto);

    expect($result->publisher)->toBe('New Publisher');
});

it('throws DuplicateIsbnException when new isbn conflicts with another book', function (): void {
    Book::factory()->create(['isbn' => '9780201633610']);
    $book = Book::factory()->create(['isbn' => '9780132350884']);

    $dto = new UpdateBookDTO(title: null, isbn: '9780201633610', description: null, publication_year: null, publisher: null, book_value: null, author_id: null, category_id: null);

    expect(fn () => $this->action->execute($book, $dto))
        ->toThrow(DuplicateIsbnException::class);
});

it('does not throw when isbn is unchanged', function (): void {
    $book = Book::factory()->create(['isbn' => '9780132350884']);

    $dto = new UpdateBookDTO(title: 'New Title', isbn: '9780132350884', description: null, publication_year: null, publisher: null, book_value: null, author_id: null, category_id: null);

    $result = $this->action->execute($book, $dto);

    expect($result->title)->toBe('New Title');
});
