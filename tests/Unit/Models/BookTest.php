<?php

declare(strict_types=1);

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

test('to array', function (): void {
    $book = Book::factory()->create()->refresh();

    expect(array_keys($book->toArray()))
        ->toContain('id', 'title', 'isbn', 'publication_year', 'book_value', 'author_id', 'category_id', 'created_at', 'updated_at', 'deleted_at')
        ->toHaveCount(10);
});

test('belongs to author', function (): void {
    $book = Book::factory()->create();

    expect($book->author())->toBeInstanceOf(BelongsTo::class);
    expect($book->author)->toBeInstanceOf(Author::class);
});

test('belongs to category', function (): void {
    $book = Book::factory()->create();

    expect($book->category())->toBeInstanceOf(BelongsTo::class);
    expect($book->category)->toBeInstanceOf(Category::class);
});

test('uses soft deletes', function (): void {
    $book = Book::factory()->create();

    expect(in_array(SoftDeletes::class, class_uses_recursive($book)))->toBeTrue();

    $book->delete();

    expect(Book::withTrashed()->find($book->id))->not->toBeNull();
    expect(Book::query()->find($book->id))->toBeNull();
});

test('factory creates valid book with author and category', function (): void {
    $book = Book::factory()->create();

    expect($book->title)->toBeString()->not->toBeEmpty();
    expect($book->isbn)->toBeString()->not->toBeEmpty();
    expect($book->publication_year)->not->toBeNull();
    expect($book->book_value)->not->toBeNull();
    expect($book->author_id)->toBeString()->not->toBeEmpty();
    expect($book->category_id)->toBeString()->not->toBeEmpty();
});
