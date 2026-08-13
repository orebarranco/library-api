<?php

declare(strict_types=1);

namespace App\Actions\Fine;

use App\DTOs\Fine\WaiveFineDTO;
use App\Enums\FineStatus;
use App\Enums\UserRole;
use App\Exceptions\Fine\FineAlreadyClosedException;
use App\Exceptions\Fine\WaiveLimitExceededException;
use App\Models\Fine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class WaiveFineAction
{
    private const float LIBRARIAN_WAIVE_LIMIT = 20.0;

    public function __construct(
        private ReconcileAccountSuspensionAction $reconcileAccountSuspension,
    ) {}

    public function execute(Fine $fine, WaiveFineDTO $dto, User $actingUser): Fine
    {
        if ($fine->status->isClosed()) {
            throw new FineAlreadyClosedException($fine->id);
        }

        $this->guardWaiveLimit($fine, $actingUser);

        DB::transaction(function () use ($fine, $dto, $actingUser): void {
            $fine->update([
                'status' => FineStatus::Waived,
                'waived_by' => $actingUser->id,
                'waived_reason' => $dto->reason,
            ]);

            // A waived fine stops counting as debt, so an account suspended over
            // it must not stay locked.
            $this->reconcileAccountSuspension->execute(User::query()->findOrFail($fine->user_id));
        });

        return $fine;
    }

    /**
     * Librarians may only waive small fines; admins have no ceiling.
     */
    private function guardWaiveLimit(Fine $fine, User $actingUser): void
    {
        if ($actingUser->role !== UserRole::Librarian) {
            return;
        }

        if ($fine->amount > self::LIBRARIAN_WAIVE_LIMIT) {
            throw new WaiveLimitExceededException($fine->id, $fine->amount, self::LIBRARIAN_WAIVE_LIMIT);
        }
    }
}
