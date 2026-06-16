<?php

declare(strict_types=1);

use App\Actions\Catalog\AddBookCopyAction;
use App\DTOs\Catalog\CreateBookCopyDTO;
use App\Models\Book;
use App\Models\BookCopy;

it('generates code from last 6 digits of isbn', function (): void {
    $book = Book::factory()->create(['isbn' => '978-3-16-148410-0']);
    $action = new AddBookCopyAction;

    $copy = $action->execute($book, new CreateBookCopyDTO(acquisition_date: null));

    expect($copy->code)->toStartWith('484100-');
});

it('increments sequence correctly for each new copy', function (): void {
    $book = Book::factory()->create(['isbn' => '9780132350884']);
    $action = new AddBookCopyAction;

    $first = $action->execute($book, new CreateBookCopyDTO(acquisition_date: null));
    $second = $action->execute($book, new CreateBookCopyDTO(acquisition_date: null));

    expect($first->code)->toEndWith('-001');
    expect($second->code)->toEndWith('-002');
});

it('pads sequence to 3 digits', function (): void {
    $book = Book::factory()->create(['isbn' => '9780132350884']);

    // Pre-create 9 copies manually with known codes
    $prefix = '350884';
    for ($i = 1; $i <= 9; $i++) {
        BookCopy::factory()->create([
            'book_id' => $book->id,
            'code' => sprintf('%s-%03d', $prefix, $i),
        ]);
    }

    $action = new AddBookCopyAction;
    $copy = $action->execute($book, new CreateBookCopyDTO(acquisition_date: null));

    expect($copy->code)->toBe("{$prefix}-010");
});
