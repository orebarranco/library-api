<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Auth;

use App\Models\User;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @property-read User $resource
 */
final class UserResource extends JsonApiResource
{
    /**
     * The resource's attributes.
     *
     * @var list<string>
     */
    public array $attributes = [
        'name',
        'email',
    ];

    /**
     * The resource's relationships.
     *
     * @var list<string>
     */
    public array $relationships = [
        // ...
    ];
}
