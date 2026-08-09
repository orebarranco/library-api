<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\Fine\GenerateFineDTO;

interface FineGenerator
{
    public function generate(GenerateFineDTO $dto): void;
}
