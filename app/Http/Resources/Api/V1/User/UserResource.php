<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\User;

use App\Models\User;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @property-read User $resource
 */
final class UserResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'name',
        'email',
        'role',
        'status',
    ];

    /**
     * @var list<string>
     */
    public array $relationships = [];
}
