<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Enums\UserRole;
use App\Http\Resources\Api\V1\Reservation\ReservationResource;
use App\Models\Reservation;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class ReservationController
{
    use ApiResponse;

    /**
     * @throws AuthorizationException
     */
    public function index(): JsonResponse
    {
        $user = request()->user();

        $query = Reservation::query();

        if ($user->role === UserRole::User) {
            $query->where('user_id', $user->id);
        }

        $reservations = QueryBuilder::for($query)
            ->allowedFilters(AllowedFilter::exact('status'))
            ->defaultSort('-created_at')
            ->paginate(15)
            ->appends(request()->query());

        return $this->successCollection(ReservationResource::collection($reservations));
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Reservation $reservation): JsonResponse
    {
        $user = request()->user();

        if ($user->role === UserRole::User && $reservation->user_id !== $user->id) {
            throw new AuthorizationException();
        }

        return $this->success(new ReservationResource($reservation));
    }
}
