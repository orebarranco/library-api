<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Fine;

use App\Http\Resources\Api\V1\Loan\LoanResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\Fine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @property-read Fine $resource
 */
final class FineResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'type',
        'amount',
        'amount_paid',
        'balance',
        'status',
        'description',
        'waived_by',
        'waived_reason',
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
        'loan' => LoanResource::class,
    ];

    public function toType(Request $request): string
    {
        return 'fines';
    }
}
