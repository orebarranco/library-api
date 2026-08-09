<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Actions\Reservation\ApproveReservationAction;
use App\Actions\Reservation\CancelReservationAction;
use App\Actions\Reservation\CreateReservationAction;
use App\Actions\Reservation\RejectReservationAction;
use App\Enums\UserRole;
use App\Http\Requests\Api\V1\Reservation\CreateReservationRequest;
use App\Http\Requests\Api\V1\Reservation\RejectReservationRequest;
use App\Http\Resources\Api\V1\Reservation\ReservationResource;
use App\Models\Reservation;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

final class ReservationController
{
    use ApiResponse;

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Reservation::query();

        if ($user->role === UserRole::User) {
            $query->where('user_id', $user->id);
        }

        $reservations = QueryBuilder::for($query)
            ->allowedFilters(AllowedFilter::exact('status'))
            ->defaultSort('-created_at')
            ->paginate(15)
            ->appends($request->query());

        return $this->successCollection(ReservationResource::collection($reservations));
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Request $request, Reservation $reservation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role === UserRole::User && $reservation->user_id !== $user->id) {
            throw new AuthorizationException();
        }

        return $this->success(new ReservationResource($reservation));
    }

    public function store(CreateReservationRequest $request, CreateReservationAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $reservation = $action->execute($request->toDto(), $user);

        return $this->success(new ReservationResource($reservation), Response::HTTP_CREATED);
    }

    public function approve(Request $request, Reservation $reservation, ApproveReservationAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $reservation = $action->execute($reservation, $user);

        return $this->success(new ReservationResource($reservation));
    }

    public function reject(RejectReservationRequest $request, Reservation $reservation, RejectReservationAction $action): JsonResponse
    {
        $reservation = $action->execute($reservation, $request->toDto());

        return $this->success(new ReservationResource($reservation));
    }

    public function destroy(Request $request, Reservation $reservation, CancelReservationAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $reservation = $action->execute($reservation, $user);

        return $this->success(new ReservationResource($reservation));
    }
}
