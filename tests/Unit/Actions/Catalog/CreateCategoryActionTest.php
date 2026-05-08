<?php

declare(strict_types=1);

use App\Actions\Catalog\CreateCategoryAction;
use App\DTOs\Catalog\CreateCategoryDTO;
use App\Models\Category;

beforeEach(function (): void {
    $this->action = new CreateCategoryAction();
});

it('creates a category and returns a Category instance', function (): void {
    $dto = new CreateCategoryDTO(
        name: 'Fiction',
        description: 'Fictional literature',
    );

    $result = $this->action->execute($dto);

    expect($result)->toBeInstanceOf(Category::class)
        ->and($result->exists)->toBeTrue()
        ->and($result->name)->toBe('Fiction')
        ->and($result->description)->toBe('Fictional literature');

    $this->assertDatabaseHas('categories', [
        'name' => 'Fiction',
        'description' => 'Fictional literature',
    ]);
});

it('creates a category without description', function (): void {
    $dto = new CreateCategoryDTO(
        name: 'Science',
        description: null,
    );

    $result = $this->action->execute($dto);

    expect($result)->toBeInstanceOf(Category::class)
        ->and($result->name)->toBe('Science')
        ->and($result->description)->toBeNull();

    $this->assertDatabaseHas('categories', [
        'name' => 'Science',
        'description' => null,
    ]);
});

it('persists category with a ULID id', function (): void {
    $dto = new CreateCategoryDTO(
        name: 'History',
        description: null,
    );

    $result = $this->action->execute($dto);

    expect($result->id)->toBeString()->not->toBeEmpty()
        ->and($result->created_at)->not->toBeNull()
        ->and($result->updated_at)->not->toBeNull();
});
