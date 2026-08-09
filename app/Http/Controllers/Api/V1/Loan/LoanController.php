<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Loan;

use App\Actions\Loan\CreateLoanAction;
use App\Actions\Loan\RenewLoanAction;
use App\Actions\Loan\ReturnLoanAction;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Http\Requests\Api\V1\Loan\CreateLoanRequest;
use App\Http\Requests\Api\V1\Loan\ReturnLoanRequest;
use App\Http\Resources\Api\V1\Loan\LoanResource;
use App\Models\Loan;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

final class LoanController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Loan::query();

        if ($user->role === UserRole::User) {
            $query->where('user_id', $user->id);
        }

        $loans = QueryBuilder::for($query)
            ->allowedFilters(AllowedFilter::exact('status'))
            ->defaultSort('-created_at')
            ->paginate(15)
            ->appends($request->query());

        return $this->successCollection(LoanResource::collection($loans));
    }

    /**
     * Loans whose due date has passed and that have not been returned yet. The
     * status column is only flipped to `overdue` by the Module 11 scheduler, so
     * the date comparison is what makes this endpoint correct in the meantime.
     */
    public function overdue(Request $request): JsonResponse
    {
        $query = Loan::query()
            ->with('user')
            ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
            ->where('due_date', '<', now());

        $loans = QueryBuilder::for($query)
            ->defaultSort('due_date')
            ->paginate(15)
            ->appends($request->query());

        return $this->successCollection(LoanResource::collection($loans));
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Request $request, Loan $loan): JsonResponse
    {
        $this->guardOwnership($request, $loan);

        return $this->success(new LoanResource($loan));
    }

    public function store(CreateLoanRequest $request, CreateLoanAction $action): JsonResponse
    {
        $loan = $action->execute($request->toDto());

        return $this->success(new LoanResource($loan), Response::HTTP_CREATED);
    }

    /**
     * @throws AuthorizationException
     */
    public function renew(Request $request, Loan $loan, RenewLoanAction $action): JsonResponse
    {
        $this->guardOwnership($request, $loan);

        $loan = $action->execute($loan);

        return $this->success(new LoanResource($loan));
    }

    public function return(ReturnLoanRequest $request, Loan $loan, ReturnLoanAction $action): JsonResponse
    {
        $loan = $action->execute($loan, $request->toDto());

        return $this->success(new LoanResource($loan));
    }

    /**
     * @throws AuthorizationException
     */
    private function guardOwnership(Request $request, Loan $loan): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role === UserRole::User && $loan->user_id !== $user->id) {
            throw new AuthorizationException();
        }
    }
}
