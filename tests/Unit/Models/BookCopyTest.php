<?php

declare(strict_types=1);

use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('belongs to book', function (): void {
    $copy = BookCopy::factory()->create();

    expect($copy->book())->toBeInstanceOf(BelongsTo::class);
    expect($copy->book)->toBeInstanceOf(Book::class);
});

it('casts status to BookCopyStatus enum', function (): void {
    $copy = BookCopy::factory()->create();

    expect($copy->status)->toBeInstanceOf(BookCopyStatus::class);
});

it('factory creates copy with available status by default', function (): void {
    $copy = BookCopy::factory()->create();

    expect($copy->status)->toBe(BookCopyStatus::Available);
});

it('factory loaned state sets correct status', function (): void {
    $copy = BookCopy::factory()->loaned()->create();

    expect($copy->status)->toBe(BookCopyStatus::Loaned);
});

it('has many loans', function (): void {
    $copy = BookCopy::factory()->create();
    Loan::factory()->count(2)->create(['book_copy_id' => $copy->id]);

    expect($copy->loans())->toBeInstanceOf(HasMany::class);
    expect($copy->loans)->toHaveCount(2);
});
