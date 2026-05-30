<?php

declare(strict_types=1);

namespace App\Exceptions\Catalog;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class BookHasActiveLoansException extends Exception
{
    public function __construct(
        public readonly string $bookId,
    ) {
        parent::__construct("Cannot delete book '{$bookId}' because it has active loans.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'errors' => [[
                'status' => (string) Response::HTTP_CONFLICT,
                'code' => 'BOOK_HAS_ACTIVE_LOANS',
                'title' => 'Book Has Active Loans',
                'detail' => $this->getMessage(),
            ]],
        ], Response::HTTP_CONFLICT, ['Content-Type' => 'application/vnd.api+json']);
    }
}
