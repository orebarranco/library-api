<?php

declare(strict_types=1);

use App\Models\Author;

test('to array', function (): void {
    $author = Author::factory()->create()->refresh();

    expect(array_keys($author->toArray()))
        ->toContain('id', 'name', 'biography', 'birth_date', 'created_at', 'updated_at')
        ->toHaveCount(6);
});
