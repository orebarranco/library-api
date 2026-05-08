<?php

declare(strict_types=1);

use App\Models\Category;

test('to array', function (): void {
    $category = Category::factory()->create()->refresh();

    expect(array_keys($category->toArray()))
        ->toContain('id', 'name', 'description', 'created_at', 'updated_at')
        ->toHaveCount(5);
});
