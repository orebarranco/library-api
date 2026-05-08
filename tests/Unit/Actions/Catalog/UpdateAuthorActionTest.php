<?php

declare(strict_types=1);

use App\Actions\Catalog\UpdateAuthorAction;
use App\DTOs\Catalog\UpdateAuthorDTO;
use App\Models\Author;

beforeEach(function (): void {
    $this->action = new UpdateAuthorAction();
});

it('updates all fields and returns the updated Author', function (): void {
    $author = Author::factory()->create([
        'name' => 'Old Name',
        'biography' => 'Old bio',
        'birth_date' => '1900-01-01',
    ]);

    $dto = new UpdateAuthorDTO(
        name: 'New Name',
        biography: 'New bio',
        birth_date: '1850-06-15',
    );

    $result = $this->action->execute($author, $dto);

    expect($result)->toBeInstanceOf(Author::class)
        ->and($result->name)->toBe('New Name')
        ->and($result->biography)->toBe('New bio')
        ->and($result->birth_date->format('Y-m-d'))->toBe('1850-06-15');

    $this->assertDatabaseHas('authors', [
        'id' => $author->id,
        'name' => 'New Name',
        'biography' => 'New bio',
    ]);
});

it('clears optional fields when null is passed', function (): void {
    $author = Author::factory()->create([
        'biography' => 'Some bio',
        'birth_date' => '1900-01-01',
    ]);

    $dto = new UpdateAuthorDTO(
        name: $author->name,
        biography: null,
        birth_date: null,
    );

    $result = $this->action->execute($author, $dto);

    expect($result->biography)->toBeNull()
        ->and($result->birth_date)->toBeNull();
});

it('returns the same Author instance that was passed in', function (): void {
    $author = Author::factory()->create();

    $dto = new UpdateAuthorDTO(
        name: 'Updated Name',
        biography: null,
        birth_date: null,
    );

    $result = $this->action->execute($author, $dto);

    expect($result->id)->toBe($author->id);
});
