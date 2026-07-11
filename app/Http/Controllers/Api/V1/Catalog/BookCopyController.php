<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\AddBookCopyAction;
use App\Actions\Catalog\ChangeBookCopyStatusAction;
use App\Actions\Catalog\DeleteBookCopyAction;
use App\Http\Requests\Api\V1\Catalog\ChangeBookCopyStatusRequest;
use App\Http\Requests\Api\V1\Catalog\CreateBookCopyRequest;
use App\Http\Resources\Api\V1\Catalog\BookCopyResource;
use App\Models\Book;
use App\Models\BookCopy;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class BookCopyController
{
    use ApiResponse;

    public function store(CreateBookCopyRequest $request, Book $book, AddBookCopyAction $action): JsonResponse
    {
        $dto = $request->toDto();
        $copy = $action->execute($book, $dto);

        return $this->success(new BookCopyResource($copy), Response::HTTP_CREATED);
    }

    public function updateStatus(ChangeBookCopyStatusRequest $request, BookCopy $bookCopy, ChangeBookCopyStatusAction $action): JsonResponse
    {
        $copy = $action->execute($bookCopy, $request->toDto());

        return $this->success(new BookCopyResource($copy));
    }

    public function destroy(BookCopy $bookCopy, DeleteBookCopyAction $action): JsonResponse
    {
        $action->execute($bookCopy);

        return $this->noData(Response::HTTP_NO_CONTENT);
    }
}
