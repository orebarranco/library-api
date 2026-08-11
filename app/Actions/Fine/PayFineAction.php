<?php

declare(strict_types=1);

namespace App\Actions\Fine;

use App\DTOs\Fine\PayFineDTO;
use App\Enums\FineStatus;
use App\Exceptions\Fine\FineAlreadyClosedException;
use App\Exceptions\Fine\PaymentExceedsBalanceException;
use App\Models\Fine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class PayFineAction
{
    public function __construct(
        private ReconcileAccountSuspensionAction $reconcileAccountSuspension,
    ) {}

    public function execute(Fine $fine, PayFineDTO $dto): Fine
    {
        if ($fine->status->isClosed()) {
            throw new FineAlreadyClosedException($fine->id);
        }

        // Compared at cent precision so paying the exact balance is never
        // rejected by a floating point remainder.
        if (round($dto->amount - $fine->balance, 2) > 0) {
            throw new PaymentExceedsBalanceException($fine->id, $fine->balance);
        }

        $amountPaid = round($fine->amount_paid + $dto->amount, 2);
        $status = $amountPaid >= $fine->amount ? FineStatus::Paid : FineStatus::PartiallyPaid;

        DB::transaction(function () use ($fine, $amountPaid, $status): void {
            $fine->update([
                'amount_paid' => $amountPaid,
                'status' => $status,
            ]);

            $this->reconcileAccountSuspension->execute(User::query()->findOrFail($fine->user_id));
        });

        return $fine;
    }
}
