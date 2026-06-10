<?php

declare(strict_types=1);

use App\Models\Author;
use App\Models\Book;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('to array', function (): void {
    $author = Author::factory()->create()->refresh();

    expect(array_keys($author->toArray()))
        ->toContain('id', 'name', 'biography', 'birth_date', 'created_at', 'updated_at')
        ->toHaveCount(6);
});

test('has many books', function (): void {
    $author = Author::factory()->create();

    expect($author->books())->toBeInstanceOf(HasMany::class);
    expect($author->books)->toBeInstanceOf(Collection::class);
});

test('books relationship returns books belonging to author', function (): void {
    $author = Author::factory()->create();
    Book::factory()->count(2)->for($author)->create();

    expect($author->books)->toHaveCount(2);
    expect($author->books->first())->toBeInstanceOf(Book::class);
});
