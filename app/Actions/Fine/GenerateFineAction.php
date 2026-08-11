<?php

declare(strict_types=1);

namespace App\Actions\Fine;

use App\Contracts\FineGenerator;
use App\DTOs\Fine\GenerateFineDTO;
use App\Enums\FineStatus;
use App\Models\Fine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class GenerateFineAction implements FineGenerator
{
    public function __construct(
        private ReconcileAccountSuspensionAction $reconcileAccountSuspension,
    ) {}

    /**
     * Contract entry point used by the loan return flow, which has no use for the
     * persisted fine.
     */
    public function generate(GenerateFineDTO $dto): void
    {
        $this->execute($dto);
    }

    public function execute(GenerateFineDTO $dto): Fine
    {
        return DB::transaction(function () use ($dto): Fine {
            $fine = Fine::query()->create([
                'user_id' => $dto->user_id,
                'loan_id' => $dto->loan_id,
                'type' => $dto->type,
                'amount' => $dto->amount,
                'amount_paid' => 0.0,
                'status' => FineStatus::Pending,
                'description' => $dto->description,
            ]);

            // Fetched explicitly rather than through the relation: strict mode
            // forbids the lazy load a fresh model would otherwise trigger.
            $this->reconcileAccountSuspension->execute(User::query()->findOrFail($dto->user_id));

            return $fine;
        });
    }
}
