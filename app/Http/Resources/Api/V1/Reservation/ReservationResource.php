<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Reservation;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @property-read Reservation $resource
 */
final class ReservationResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'status',
        'reserved_at',
        'approved_at',
        'approved_by',
        'expires_at',
        'reason',
        'created_at',
    ];

    /**
     * @var list<string>
     */
    public array $relationships = [
        'user',
        'book',
    ];

    public function toType(Request $request): string
    {
        return 'reservations';
    }
}
