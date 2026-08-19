<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Report;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * A borrower with the volume of activity they generated inside the reported
 * window. Kept apart from UserResource so an activity report never leaks the
 * account fields that resource exposes.
 *
 * @property-read User $resource
 */
final class UserActivityResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'name',
        'email',
        'role',
        'loans_count',
        'reservations_count',
        'fines_count',
    ];

    public function toType(Request $request): string
    {
        return 'user-activity';
    }
}
