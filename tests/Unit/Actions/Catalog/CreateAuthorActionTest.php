<?php

declare(strict_types=1);

use App\Actions\Catalog\CreateAuthorAction;
use App\DTOs\Catalog\CreateAuthorDTO;
use App\Models\Author;

beforeEach(function (): void {
    $this->action = new CreateAuthorAction();
});

it('creates an author and returns an Author instance', function (): void {
    $dto = new CreateAuthorDTO(
        name: 'Mark Twain',
        biography: 'American writer and humorist.',
        birth_date: '1835-11-30',
    );

    $result = $this->action->execute($dto);

    expect($result)->toBeInstanceOf(Author::class)
        ->and($result->exists)->toBeTrue()
        ->and($result->name)->toBe('Mark Twain')
        ->and($result->biography)->toBe('American writer and humorist.')
        ->and($result->birth_date->format('Y-m-d'))->toBe('1835-11-30');

    $this->assertDatabaseHas('authors', [
        'name' => 'Mark Twain',
        'biography' => 'American writer and humorist.',
    ]);
});

it('creates an author without optional fields', function (): void {
    $dto = new CreateAuthorDTO(
        name: 'Unknown Author',
        biography: null,
        birth_date: null,
    );

    $result = $this->action->execute($dto);

    expect($result)->toBeInstanceOf(Author::class)
        ->and($result->name)->toBe('Unknown Author')
        ->and($result->biography)->toBeNull()
        ->and($result->birth_date)->toBeNull();
});

it('persists author with a ULID id', function (): void {
    $dto = new CreateAuthorDTO(
        name: 'George Orwell',
        biography: null,
        birth_date: null,
    );

    $result = $this->action->execute($dto);

    expect($result->id)->toBeString()->not->toBeEmpty()
        ->and($result->created_at)->not->toBeNull()
        ->and($result->updated_at)->not->toBeNull();
});
