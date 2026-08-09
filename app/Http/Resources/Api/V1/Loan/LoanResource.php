<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Loan;

use App\Http\Resources\Api\V1\Catalog\BookCopyResource;
use App\Http\Resources\Api\V1\Reservation\ReservationResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * @property-read Loan $resource
 */
final class LoanResource extends JsonApiResource
{
    /**
     * @var list<string>
     */
    public array $attributes = [
        'status',
        'loaned_at',
        'due_date',
        'returned_at',
        'renewal_count',
        'days_overdue',
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
        'bookCopy' => BookCopyResource::class,
        'reservation' => ReservationResource::class,
    ];

    public function toType(Request $request): string
    {
        return 'loans';
    }
}
