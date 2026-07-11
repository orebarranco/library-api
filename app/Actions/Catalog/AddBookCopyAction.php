<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\DTOs\Catalog\CreateBookCopyDTO;
use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Support\Facades\DB;

final class AddBookCopyAction
{
    public function execute(Book $book, CreateBookCopyDTO $dto): BookCopy
    {
        return DB::transaction(function () use ($book, $dto): BookCopy {
            // Serialize concurrent copy creation for the same book
            Book::query()->whereKey($book->id)->lockForUpdate()->first();

            $prefix = $this->extractIsbnPrefix($book->isbn);
            $nextSuffix = $this->resolveNextSuffix($book, $prefix);
            $code = sprintf('%s-%03d', $prefix, $nextSuffix);

            return BookCopy::query()->create([
                'book_id' => $book->id,
                'code' => $code,
                'status' => BookCopyStatus::Available,
                'acquisition_date' => $dto->acquisition_date,
            ]);
        });
    }

    private function extractIsbnPrefix(string $isbn): string
    {
        // Strip all non-digit characters, then take the last 6 digits
        $digits = preg_replace('/\D/', '', $isbn);

        return mb_substr((string) $digits, -6);
    }

    private function resolveNextSuffix(Book $book, string $prefix): int
    {
        // Find the highest existing numeric suffix for this book's copies
        $highestCode = $book->copies()
            ->where('code', 'like', "{$prefix}-%")
            ->orderByDesc('code')
            ->value('code');

        if (! is_string($highestCode)) {
            return 1;
        }

        // Parse the suffix after the last '-'
        $parts = explode('-', $highestCode);
        $currentSuffix = (int) end($parts);

        return $currentSuffix + 1;
    }
}
