<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Report;

use App\Http\Resources\Api\V1\Catalog\AuthorResource;
use App\Http\Resources\Api\V1\Catalog\CategoryResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * A ranked book. Kept separate from BookResource because `total_loans` only
 * exists on a query that aggregates it, not on the book itself.
 *
 * @property-read Book $resource
 */
final class PopularBookResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'title',
        'isbn',
        'publication_year',
        'total_loans',
    ];

    /**
     * Resource classes are declared explicitly because auto-discovery does not
     * resolve this application's versioned resource namespaces.
     *
     * @var array<string, class-string>
     */
    public array $relationships = [
        'author' => AuthorResource::class,
        'category' => CategoryResource::class,
    ];

    public function toType(Request $request): string
    {
        return 'popular-books';
    }
}
