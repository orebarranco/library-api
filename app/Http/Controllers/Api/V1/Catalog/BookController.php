<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\CreateBookAction;
use App\Actions\Catalog\DeleteBookAction;
use App\Actions\Catalog\UpdateBookAction;
use App\Http\Requests\Api\V1\Catalog\CreateBookRequest;
use App\Http\Requests\Api\V1\Catalog\UpdateBookRequest;
use App\Http\Resources\Api\V1\Catalog\BookResource;
use App\Models\Book;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
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
        $books = QueryBuilder::for(Book::with(['author', 'category']))
            ->allowedFilters(
                AllowedFilter::exact('author_id'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::callback('search', function (Builder $query, string $value): void {
                    $query->where(function (Builder $q) use ($value): void {
                        $q->where('title', 'like', "%{$value}%")
                            ->orWhere('isbn', 'like', "%{$value}%")
                            ->orWhere('publisher', 'like', "%{$value}%")
                            ->orWhereHas('author', fn (Builder $q2) => $q2->where('name', 'like', "%{$value}%"));
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('title'),
                AllowedSort::field('publication_year'),
                AllowedSort::field('created_at'),
            )
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
        $action->execute($book);

        return $this->noData(Response::HTTP_NO_CONTENT);
    }
}
