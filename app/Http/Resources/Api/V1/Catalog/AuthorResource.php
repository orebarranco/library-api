<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Catalog;

use App\Models\Author;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @property-read Author $resource
 */
final class AuthorResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'name',
        'biography',
        'birth_date',
    ];

    /**
     * @var list<string>
     */
    public array $relationships = [];
}
