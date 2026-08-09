<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Catalog;

use App\Models\BookCopy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @property-read BookCopy $resource
 */
final class BookCopyResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'code',
        'status',
        'acquisition_date',
    ];

    /**
     * Resource classes are declared explicitly because auto-discovery does not
     * resolve this application's versioned resource namespaces.
     *
     * @var array<string, class-string>
     */
    public array $relationships = [
        'book' => BookResource::class,
    ];

    public function toType(Request $request): string
    {
        return 'book-copies';
    }
}
