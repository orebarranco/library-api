<?php

declare(strict_types=1);

use App\Http\Resources\Api\V1\Auth\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->subject = new class
    {
        use ApiResponse;

        public function callSuccessCollection(AnonymousResourceCollection $collection, int $status = Response::HTTP_OK): JsonResponse
        {
            return $this->successCollection($collection, $status);
        }
    };
});

it('successCollection returns paginated response with JSON:API structure', function (): void {
    $users = User::factory()->count(3)->make();
    $paginator = new LengthAwarePaginator($users, 10, 3, 1, ['path' => 'http://localhost/api/v1/users']);
    $collection = UserResource::collection($paginator);

    $response = $this->subject->callSuccessCollection($collection);
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_OK)
        ->and($data)->toHaveKeys(['data', 'links', 'meta'])
        ->and($data['links'])->toMatchArray([
            'first' => 'http://localhost/api/v1/users?page=1',
            'last' => 'http://localhost/api/v1/users?page=4',
            'prev' => null,
            'next' => 'http://localhost/api/v1/users?page=2',
        ])
        ->and($data['meta']['pagination'])->toMatchArray([
            'total' => 10,
            'per_page' => 3,
            'current_page' => 1,
            'last_page' => 4,
        ]);
});

it('successCollection resolves JSON:API resource objects correctly', function (): void {
    $users = User::factory()->count(2)->make();
    $paginator = new LengthAwarePaginator($users, 2, 15, 1, ['path' => 'http://localhost/api/v1/users']);
    $collection = UserResource::collection($paginator);

    $response = $this->subject->callSuccessCollection($collection);
    $data = $response->getData(true);

    expect($data['data'])->toBeArray()->toHaveCount(2)
        ->and($data['data'][0])->toHaveKeys(['id', 'type', 'attributes'])
        ->and($data['data'][0]['type'])->toBe('users');
});

it('successCollection accepts a custom status code', function (): void {
    $users = User::factory()->count(1)->make();
    $paginator = new LengthAwarePaginator($users, 1, 15, 1, ['path' => 'http://localhost/api/v1/users']);
    $collection = UserResource::collection($paginator);

    $response = $this->subject->callSuccessCollection($collection, Response::HTTP_PARTIAL_CONTENT);

    expect($response->status())->toBe(Response::HTTP_PARTIAL_CONTENT);
});

it('validationError skips non-string messages', function (): void {
    $subject = new class
    {
        use ApiResponse;

        /** @param array<string, mixed> $errors */
        public function callValidationError(array $errors): JsonResponse
        {
            return $this->validationError($errors);
        }
    };

    $response = $subject->callValidationError(['email' => [null, 'The email is required.', 42]]);
    $data = $response->getData(true);

    expect($data['errors'])->toHaveCount(1)
        ->and($data['errors'][0]['detail'])->toBe('The email is required.');
});

it('successAttributes renders a computed JSON:API document', function (): void {
    $subject = new class
    {
        use ApiResponse;

        /** @param array<string, mixed> $attributes */
        public function callSuccessAttributes(string $type, string $id, array $attributes): JsonResponse
        {
            return $this->successAttributes($type, $id, $attributes);
        }
    };

    $response = $subject->callSuccessAttributes('fine-summaries', 'user-123', ['pending_total' => 40.0]);
    $data = $response->getData(true);

    expect($response->status())->toBe(Response::HTTP_OK)
        ->and($response->headers->get('Content-Type'))->toBe('application/vnd.api+json')
        ->and($data['data'])->toMatchArray([
            'type' => 'fine-summaries',
            'id' => 'user-123',
            'attributes' => ['pending_total' => 40.0],
        ])
        ->and($data['meta'])->toHaveKeys(['request_id', 'version', 'timestamp']);
});

it('successCollection includes base meta fields', function (): void {
    $users = User::factory()->count(1)->make();
    $paginator = new LengthAwarePaginator($users, 1, 15, 1, ['path' => 'http://localhost/api/v1/users']);
    $collection = UserResource::collection($paginator);

    $response = $this->subject->callSuccessCollection($collection);
    $meta = $response->getData(true)['meta'];

    expect($meta)->toHaveKeys(['request_id', 'version', 'timestamp', 'pagination']);
});
