<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Report;

use App\Http\Resources\Api\V1\Catalog\AuthorResource;
use App\Http\Resources\Api\V1\Catalog\CategoryResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * A title with the size of its physical stock broken down by copy status. The
 * counts only exist on a query that aggregates them, which is why this is not
 * folded into BookResource.
 *
 * @property-read Book $resource
 */
final class InventoryStatusResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'title',
        'isbn',
        'total_copies',
        'available_copies',
        'loaned_copies',
        'maintenance_copies',
        'lost_copies',
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
        return 'inventory-status';
    }
}
