<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\AuditLog;

use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @property-read AuditLog $resource
 */
final class AuditLogResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'created_at',
    ];

    /**
     * Resource classes are declared explicitly because auto-discovery does not
     * resolve this application's versioned resource namespaces.
     *
     * @var array<string, class-string>
     */
    public array $relationships = [
        'user' => UserResource::class,
    ];

    public function toType(Request $request): string
    {
        return 'audit-logs';
    }
}
