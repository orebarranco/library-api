<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Catalog;

use App\Models\Book;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @property-read Book $resource
 */
final class BookResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'title',
        'isbn',
        'publication_year',
        'book_value',
    ];

    /**
     * @var list<string>
     */
    public array $relationships = [
        'author',
        'category',
    ];
}
