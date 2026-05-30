<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\CreateBookAction;
use App\Actions\Catalog\DeleteBookAction;
use App\Actions\Catalog\UpdateBookAction;
use App\Exceptions\Catalog\BookHasActiveLoansException;
use App\Http\Requests\Api\V1\Catalog\CreateBookRequest;
use App\Http\Requests\Api\V1\Catalog\UpdateBookRequest;
use App\Http\Resources\Api\V1\Catalog\BookResource;
use App\Models\Book;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

final class BookController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $books = QueryBuilder::for(Book::class)
            ->allowedFilters(
                AllowedFilter::exact('author_id'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::callback('search', function ($query, $value): void {
                    $query->where(function ($q) use ($value): void {
                        $q->where('title', 'like', "%{$value}%")
                            ->orWhere('isbn', 'like', "%{$value}%")
                            ->orWhere('publisher', 'like', "%{$value}%")
                            ->orWhereHas('author', fn ($q2) => $q2->where('name', 'like', "%{$value}%"));
                    });
                }),
                AllowedFilter::callback('available_only', fn ($query, $value) => null), // No-op until Module 6
            )
            ->allowedSorts(
                AllowedSort::field('title'),
                AllowedSort::field('publication_year'),
                AllowedSort::field('created_at'),
                AllowedSort::callback('popularity', fn ($query, $descending) => null), // No-op until Module 7
            )
            ->with(['author', 'category'])
            ->defaultSort('title')
            ->paginate(15);

        return $this->successCollection(BookResource::collection($books));
    }

    public function show(Book $book): JsonResponse
    {
        $book->load(['author', 'category']);

        return $this->success(new BookResource($book));
    }

    public function store(CreateBookRequest $request, CreateBookAction $action): JsonResponse
    {
        $book = $action->execute($request->toDto());
        $book->load(['author', 'category']);

        return $this->success(new BookResource($book), Response::HTTP_CREATED);
    }

    public function update(UpdateBookRequest $request, Book $book, UpdateBookAction $action): JsonResponse
    {
        $book = $action->execute($book, $request->toDto());
        $book->load(['author', 'category']);

        return $this->success(new BookResource($book));
    }

    public function destroy(Book $book, DeleteBookAction $action): JsonResponse
    {
        try {
            $action->execute($book);
        } catch (BookHasActiveLoansException $e) {
            return response()->json([
                'errors' => [[
                    'status' => (string) Response::HTTP_CONFLICT,
                    'code' => 'BOOK_HAS_ACTIVE_LOANS',
                    'title' => 'Book Has Active Loans',
                    'detail' => $e->getMessage(),
                ]],
            ], Response::HTTP_CONFLICT, ['Content-Type' => 'application/vnd.api+json']);
        }

        return $this->noData(Response::HTTP_NO_CONTENT);
    }
}
