<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Catalog;

use App\Models\Category;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @property-read Category $resource
 */
final class CategoryResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'name',
        'description',
    ];

    /**
     * @var list<string>
     */
    public array $relationships = [];
}
