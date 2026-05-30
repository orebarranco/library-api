<?php

declare(strict_types=1);

namespace App\Exceptions\Catalog;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DuplicateIsbnException extends Exception
{
    public function __construct(
        public readonly string $isbn,
    ) {
        parent::__construct("A book with ISBN '{$isbn}' already exists.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'errors' => [[
                'status' => (string) Response::HTTP_CONFLICT,
                'code' => 'DUPLICATE_ISBN',
                'title' => 'Duplicate ISBN',
                'detail' => $this->getMessage(),
            ]],
        ], Response::HTTP_CONFLICT, ['Content-Type' => 'application/vnd.api+json']);
    }
}
