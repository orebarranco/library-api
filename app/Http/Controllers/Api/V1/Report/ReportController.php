<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Report;

use App\Enums\BookCopyStatus;
use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Enums\ReportPeriod;
use App\Enums\ReservationStatus;
use App\Enums\TrendType;
use App\Http\Requests\Api\V1\Report\PeriodRequest;
use App\Http\Resources\Api\V1\Loan\LoanResource;
use App\Http\Resources\Api\V1\Report\InventoryStatusResource;
use App\Http\Resources\Api\V1\Report\PopularBookResource;
use App\Http\Resources\Api\V1\Report\UserActivityResource;
use App\Http\Resources\Api\V1\Reservation\ReservationResource;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
    public function popularBooks(PeriodRequest $request): JsonResponse
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
     * Borrowers ranked by how much they used the library inside the window.
     * Every account is listed, inactive ones last with zeroed counts, so the
     * report doubles as a roster.
     */
    public function userActivity(PeriodRequest $request): JsonResponse
    {
        $period = $request->period();

        $users = User::query()
            ->withCount([
                'loans as loans_count' => $this->countedSince($period, 'loaned_at'),
                'reservations as reservations_count' => $this->countedSince($period, 'reserved_at'),
                'fines as fines_count' => $this->countedSince($period, 'created_at'),
            ])
            ->orderByDesc('loans_count')
            ->orderByDesc('reservations_count')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->appends($request->query());

        return $this->successCollection(UserActivityResource::collection($users));
    }

    /**
     * Stock of every title broken down by copy status. A snapshot of the
     * shelves as they stand, so no period filter applies.
     */
    public function inventoryStatus(Request $request): JsonResponse
    {
        $books = Book::query()
            ->withCount([
                'copies as total_copies',
                'copies as available_copies' => $this->countedWithStatus(BookCopyStatus::Available),
                'copies as loaned_copies' => $this->countedWithStatus(BookCopyStatus::Loaned),
                'copies as maintenance_copies' => $this->countedWithStatus(BookCopyStatus::Maintenance),
                'copies as lost_copies' => $this->countedWithStatus(BookCopyStatus::Lost),
            ])
            ->orderBy('title')
            ->paginate(self::PER_PAGE)
            ->appends($request->query());

        return $this->successCollection(InventoryStatusResource::collection($books));
    }

    /**
     * Money the fine system moved during the window. `total_collected` is
     * derived from the amount_paid of fines touched in the window: without a
     * payment ledger there is no finer record of when each instalment landed,
     * the same approximation the weekly report job makes.
     */
    public function finesRevenue(PeriodRequest $request): JsonResponse
    {
        $period = $request->period();

        $generated = Fine::query()->when($period, fn (Builder $query, ReportPeriod $window) => $query->where('created_at', '>=', $window->since()));
        $settled = Fine::query()->when($period, fn (Builder $query, ReportPeriod $window) => $query->where('updated_at', '>=', $window->since()));

        return $this->successAttributes('fines-revenue', 'current', [
            'period' => $period?->value,
            'fines_generated_count' => (clone $generated)->count(),
            'total_generated' => $this->money((float) (clone $generated)->sum('amount')),
            'total_collected' => $this->money((float) (clone $settled)->sum('amount_paid')),
            'total_waived' => $this->money((float) (clone $settled)->where('status', FineStatus::Waived)->sum('amount')),
            'total_outstanding' => $this->money((float) (clone $generated)->whereIn('status', FineStatus::outstanding())->sum(DB::raw('amount - amount_paid'))),
        ]);
    }

    /**
     * Loan volume broken down by category or by calendar month. A trend over
     * unbounded history says little, so the window defaults to one year.
     */
    public function trends(TrendType $type, PeriodRequest $request): JsonResponse
    {
        $period = $request->period() ?? ReportPeriod::OneYear;

        $series = match ($type) {
            TrendType::Category => $this->loansByCategory($period->since()),
            TrendType::Month => $this->loansByMonth($period->since()),
        };

        return $this->successAttributes('trends', $type->value, [
            'type' => $type->value,
            'period' => $period->value,
            'series' => $series,
        ]);
    }

    /**
     * A withCount constraint that keeps only rows dated inside the window, or
     * every row when the report covers all time.
     *
     * @return callable(Builder<covariant Model>):void
     */
    private function countedSince(?ReportPeriod $period, string $column): callable
    {
        return function (Builder $query) use ($period, $column): void {
            if ($period instanceof ReportPeriod) {
                $query->where($column, '>=', $period->since());
            }
        };
    }

    /**
     * @return callable(Builder<BookCopy>):void
     */
    private function countedWithStatus(BookCopyStatus $status): callable
    {
        return function (Builder $query) use ($status): void {
            $query->where('status', $status);
        };
    }

    /**
     * Categories ranked by loan volume. Loans reach a category through the copy
     * they were taken on, hence the two joins.
     *
     * @return list<array{label: string, total: int}>
     */
    private function loansByCategory(CarbonInterface $since): array
    {
        $names = Loan::query()
            ->join('book_copies', 'loans.book_copy_id', '=', 'book_copies.id')
            ->join('books', 'book_copies.book_id', '=', 'books.id')
            ->join('categories', 'books.category_id', '=', 'categories.id')
            ->where('loans.loaned_at', '>=', $since)
            ->pluck('categories.name');

        return $this->series(
            $names->countBy()->sortKeys()->sortByDesc(fn (int $total): int => $total)
        );
    }

    /**
     * Loan volume per calendar month, oldest first. The grouping happens in PHP
     * because month extraction has no portable SQL spelling, and the window
     * keeps the pulled column small.
     *
     * @return list<array{label: string, total: int}>
     */
    private function loansByMonth(CarbonInterface $since): array
    {
        $loans = Loan::query()
            ->where('loaned_at', '>=', $since)
            ->get(['loaned_at']);

        return $this->series(
            $loans->countBy(fn (Loan $loan): string => $loan->loaned_at->format('Y-m'))->sortKeys()
        );
    }

    /**
     * Turns a label-to-count tally into the ordered series the API returns,
     * keeping the order the tally already carries.
     *
     * @param  Collection<array-key, int>  $counts
     * @return list<array{label: string, total: int}>
     */
    private function series(Collection $counts): array
    {
        $series = [];

        foreach ($counts as $label => $total) {
            $series[] = ['label' => (string) $label, 'total' => $total];
        }

        return $series;
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
        return $this->money((float) Fine::query()
            ->whereIn('status', FineStatus::outstanding())
            ->sum(DB::raw('amount - amount_paid')));
    }

    /**
     * Monetary aggregates come back from the driver as strings or integers;
     * the API always reports them as two-decimal floats.
     */
    private function money(float $amount): float
    {
        return round($amount, 2);
    }
}
