<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\Fine\GenerateFineDTO;

final class NullFineGenerator implements FineGenerator
{
    public function generate(GenerateFineDTO $dto): void
    {
        // Fines are persisted by Module 9. Until then, generation is a no-op.
    }
}
