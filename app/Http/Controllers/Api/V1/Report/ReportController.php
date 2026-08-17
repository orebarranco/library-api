<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Report;

use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Enums\ReportPeriod;
use App\Enums\ReservationStatus;
use App\Http\Requests\Api\V1\Report\PopularBooksRequest;
use App\Http\Resources\Api\V1\Loan\LoanResource;
use App\Http\Resources\Api\V1\Report\PopularBookResource;
use App\Http\Resources\Api\V1\Reservation\ReservationResource;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

final class ReportController
{
    use ApiResponse;

    private const int PER_PAGE = 15;

    /**
     * Snapshot of current library activity. `overdue_loans_count` counts a subset
     * of `active_loans_count`: an overdue loan is still out on loan.
     */
    public function dashboard(): JsonResponse
    {
        return $this->successAttributes('dashboards', 'current', [
            'active_loans_count' => $this->activeLoansQuery()->count(),
            'overdue_loans_count' => $this->overdueLoansQuery()->count(),
            'pending_reservations_count' => $this->pendingReservationsQuery()->count(),
            'total_pending_fines_amount' => $this->totalPendingFines(),
        ]);
    }

    public function activeLoans(Request $request): JsonResponse
    {
        $loans = QueryBuilder::for($this->activeLoansQuery()->with('user'))
            ->defaultSort('due_date')
            ->paginate(self::PER_PAGE)
            ->appends($request->query());

        return $this->successCollection(LoanResource::collection($loans));
    }

    /**
     * Each entry carries `days_overdue` through LoanResource.
     */
    public function overdueLoans(Request $request): JsonResponse
    {
        $loans = QueryBuilder::for($this->overdueLoansQuery()->with('user'))
            ->defaultSort('due_date')
            ->paginate(self::PER_PAGE)
            ->appends($request->query());

        return $this->successCollection(LoanResource::collection($loans));
    }

    public function pendingReservations(Request $request): JsonResponse
    {
        $reservations = QueryBuilder::for($this->pendingReservationsQuery()->with(['user', 'book']))
            ->defaultSort('reserved_at')
            ->paginate(self::PER_PAGE)
            ->appends($request->query());

        return $this->successCollection(ReservationResource::collection($reservations));
    }

    /**
     * Titles ranked by how often any of their copies has been loaned out. Books
     * that were never loaned still appear, at the bottom with a count of zero.
     */
    public function popularBooks(PopularBooksRequest $request): JsonResponse
    {
        $period = $request->period();

        $books = Book::query()
            ->withCount(['loans as total_loans' => function (Builder $query) use ($period): void {
                if ($period instanceof ReportPeriod) {
                    $query->where('loans.loaned_at', '>=', $period->since());
                }
            }])
            ->orderByDesc('total_loans')
            ->orderBy('title')
            ->paginate(self::PER_PAGE)
            ->appends($request->query());

        return $this->successCollection(PopularBookResource::collection($books));
    }

    /**
     * Loans whose copy has not come back yet, overdue ones included.
     *
     * @return Builder<Loan>
     */
    private function activeLoansQuery(): Builder
    {
        return Loan::query()->whereIn('status', LoanStatus::open());
    }

    /**
     * The status column is only flipped to `overdue` by the Module 11 scheduler,
     * so the date comparison is what keeps this correct between runs.
     *
     * @return Builder<Loan>
     */
    private function overdueLoansQuery(): Builder
    {
        return $this->activeLoansQuery()->where('due_date', '<', now());
    }

    /**
     * @return Builder<Reservation>
     */
    private function pendingReservationsQuery(): Builder
    {
        return Reservation::query()->where('status', ReservationStatus::Pending);
    }

    /**
     * Unpaid remainder of every fine that is neither paid nor waived, across all
     * borrowers.
     */
    private function totalPendingFines(): float
    {
        return round((float) Fine::query()
            ->whereIn('status', FineStatus::outstanding())
            ->sum(DB::raw('amount - amount_paid')), 2);
    }
}
