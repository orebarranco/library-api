<?php

declare(strict_types=1);

namespace App\Actions\Reservation;

use App\Contracts\FineChecker;
use App\Contracts\LoanChecker;
use App\DTOs\Reservation\CreateReservationDTO;
use App\Enums\BookCopyStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Exceptions\Reservation\DuplicateReservationException;
use App\Exceptions\Reservation\NoCopiesAvailableException;
use App\Exceptions\Reservation\OverdueLoansException;
use App\Exceptions\Reservation\ReservationLimitExceededException;
use App\Exceptions\Reservation\UnpaidFinesException;
use App\Models\BookCopy;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\NewReservationNotification;
use Illuminate\Support\Facades\Notification;

final readonly class CreateReservationAction
{
    private const int MAX_ACTIVE_RESERVATIONS = 3;

    private const float MAX_UNPAID_FINES = 50.0;

    public function __construct(
        private LoanChecker $loanChecker,
        private FineChecker $fineChecker,
    ) {}

    public function execute(CreateReservationDTO $dto, User $actingUser): Reservation
    {
        $this->guardAvailableCopy($dto->book_id);
        $this->guardNoDuplicateReservation($dto->book_id, $actingUser);
        $this->guardReservationLimit($actingUser);
        $this->guardNoUnpaidFines($actingUser);
        $this->guardNoOverdueLoans($actingUser);

        $reservation = Reservation::query()->create([
            'user_id' => $actingUser->id,
            'book_id' => $dto->book_id,
            'status' => ReservationStatus::Pending,
            'reserved_at' => now(),
        ]);

        $librarians = User::query()->where('role', UserRole::Librarian)->get();
        Notification::send($librarians, new NewReservationNotification($reservation));

        return $reservation;
    }

    private function guardAvailableCopy(string $bookId): void
    {
        $hasAvailableCopy = BookCopy::query()
            ->where('book_id', $bookId)
            ->where('status', BookCopyStatus::Available)
            ->exists();

        if (! $hasAvailableCopy) {
            throw new NoCopiesAvailableException($bookId);
        }
    }

    private function guardNoDuplicateReservation(string $bookId, User $actingUser): void
    {
        $hasDuplicate = Reservation::query()
            ->where('user_id', $actingUser->id)
            ->where('book_id', $bookId)
            ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Approved])
            ->exists();

        if ($hasDuplicate) {
            throw new DuplicateReservationException($bookId);
        }
    }

    private function guardReservationLimit(User $actingUser): void
    {
        $activeCount = Reservation::query()
            ->where('user_id', $actingUser->id)
            ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Approved])
            ->count();

        if ($activeCount >= self::MAX_ACTIVE_RESERVATIONS) {
            throw new ReservationLimitExceededException($actingUser->id);
        }
    }

    private function guardNoUnpaidFines(User $actingUser): void
    {
        if ($this->fineChecker->pendingFinesTotal($actingUser) >= self::MAX_UNPAID_FINES) {
            throw new UnpaidFinesException($actingUser->id);
        }
    }

    private function guardNoOverdueLoans(User $actingUser): void
    {
        if ($this->loanChecker->hasOverdueLoans($actingUser)) {
            throw new OverdueLoansException($actingUser->id);
        }
    }
}
