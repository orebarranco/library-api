<?php

declare(strict_types=1);

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('to array', function (): void {
    $category = Category::factory()->create()->refresh();

    expect(array_keys($category->toArray()))
        ->toContain('id', 'name', 'description', 'created_at', 'updated_at')
        ->toHaveCount(5);
});

test('has many books', function (): void {
    $category = Category::factory()->create();

    expect($category->books())->toBeInstanceOf(HasMany::class);
    expect($category->books)->toBeInstanceOf(Collection::class);
});

test('books relationship returns books belonging to category', function (): void {
    $category = Category::factory()->create();
    Book::factory()->count(2)->for($category)->create();

    expect($category->books)->toHaveCount(2);
    expect($category->books->first())->toBeInstanceOf(Book::class);
});
