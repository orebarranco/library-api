<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Fine;

use App\Actions\Fine\PayFineAction;
use App\Actions\Fine\WaiveFineAction;
use App\Enums\FineStatus;
use App\Enums\UserRole;
use App\Http\Requests\Api\V1\Fine\PayFineRequest;
use App\Http\Requests\Api\V1\Fine\WaiveFineRequest;
use App\Http\Resources\Api\V1\Fine\FineResource;
use App\Models\Fine;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class FineController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Fine::query();

        if ($user->role === UserRole::User) {
            $query->where('user_id', $user->id);
        }

        $fines = QueryBuilder::for($query)
            ->allowedFilters(AllowedFilter::exact('status'))
            ->defaultSort('-created_at')
            ->paginate(15)
            ->appends($request->query());

        return $this->successCollection(FineResource::collection($fines));
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Request $request, Fine $fine): JsonResponse
    {
        $this->guardOwnership($request, $fine);

        return $this->success(new FineResource($fine));
    }

    /**
     * @throws AuthorizationException
     */
    public function pay(PayFineRequest $request, Fine $fine, PayFineAction $action): JsonResponse
    {
        $this->guardOwnership($request, $fine);

        return $this->success(new FineResource($action->execute($fine, $request->toDto())));
    }

    public function waive(WaiveFineRequest $request, Fine $fine, WaiveFineAction $action): JsonResponse
    {
        /** @var User $actingUser */
        $actingUser = $request->user();

        return $this->success(new FineResource($action->execute($fine, $request->toDto(), $actingUser)));
    }

    /**
     * Outstanding debt for a single borrower, used by librarians before approving
     * a reservation or a loan.
     */
    public function summary(User $user): JsonResponse
    {
        $pendingCount = $user->fines()
            ->whereIn('status', FineStatus::outstanding())
            ->count();

        return $this->successAttributes('fine-summaries', $user->id, [
            'pending_total' => $user->pending_fines_total,
            'pending_count' => $pendingCount,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    private function guardOwnership(Request $request, Fine $fine): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role === UserRole::User && $fine->user_id !== $user->id) {
            throw new AuthorizationException();
        }
    }
}
