<?php

declare(strict_types=1);

use App\Models\Book;
use App\Models\BookCopy;

beforeEach(function (): void {
    $this->book = Book::factory()->create();
    $this->endpoint = "/api/v1/books/{$this->book->id}/copies";
});

it('anyone can list copies for a book', function (): void {
    $this->getJson($this->endpoint)
        ->assertSuccessful();
});

it('returns all copies with their status', function (): void {
    BookCopy::factory()->count(3)->create(['book_id' => $this->book->id]);

    $this->getJson($this->endpoint)
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('returns empty array when book has no copies', function (): void {
    $this->getJson($this->endpoint)
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('returns 404 for non-existent book', function (): void {
    $this->getJson('/api/v1/books/non-existent-id/copies')
        ->assertNotFound();
});
