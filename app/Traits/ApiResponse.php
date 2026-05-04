<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * @return array{request_id: string, version: mixed, timestamp: string}
     */
    protected function baseMeta(): array
    {
        return [
            'request_id' => request()->header('X-Request-ID', (string) Str::uuid()),
            'version' => request()->attributes->get('api_version', 'v1'),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function success(JsonApiResource $resource, int $status = Response::HTTP_OK, array $meta = []): JsonResponse
    {
        return $resource
            ->additional(['meta' => array_merge($this->baseMeta(), $meta)])
            ->response()
            ->setStatusCode($status);
    }

    protected function successCollection(AnonymousResourceCollection $collection, int $status = Response::HTTP_OK): JsonResponse
    {
        $paginator = $collection->resource;

        $additional = ['meta' => $this->baseMeta()];

        if ($paginator instanceof LengthAwarePaginator) {
            $additional['meta']['pagination'] = [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ];
        }

        return $collection
            ->additional($additional)
            ->response()
            ->setStatusCode($status);
    }

    protected function noData(int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json(
            ['meta' => $this->baseMeta()],
            $status,
            ['Content-Type' => 'application/vnd.api+json'],
        );
    }

    protected function error(string $message, string $code, string $detail = '', int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'errors' => [
                [
                    'status' => (string) $status,
                    'code' => $code,
                    'title' => $message,
                    'detail' => $detail ?: $message,
                ],
            ],
            'meta' => $this->baseMeta(),
        ], $status, ['Content-Type' => 'application/vnd.api+json']);
    }

    /**
     * @param  array<array-key, mixed>  $errors
     */
    protected function validationError(array $errors, string $message = 'The given data was invalid.'): JsonResponse
    {
        $jsonApiErrors = collect($errors)
            ->flatMap(fn (mixed $messages, mixed $field) => collect(array_filter((array) $messages, is_string(...)))
                ->map(fn (string $detail): array => [
                    'status' => (string) Response::HTTP_UNPROCESSABLE_ENTITY,
                    'code' => 'VALIDATION_ERROR',
                    'title' => $message,
                    'detail' => $detail,
                    'source' => ['pointer' => '/data/attributes/'.$field],
                ])
            )
            ->values()
            ->all();

        return response()->json([
            'errors' => $jsonApiErrors,
            'meta' => $this->baseMeta(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY, ['Content-Type' => 'application/vnd.api+json']);
    }
}
