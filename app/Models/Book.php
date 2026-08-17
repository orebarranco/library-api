<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $title
 * @property string $isbn
 * @property string $description
 * @property int $publication_year
 * @property string|null $publisher
 * @property string $book_value
 * @property string $author_id
 * @property string $category_id
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Author $author
 * @property-read Category $category
 * @property-read Collection<int, BookCopy> $copies
 * @property-read Collection<int, Reservation> $reservations
 * @property-read Collection<int, Loan> $loans
 * @property-read int $total_loans
 */
final class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    use HasUlids;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'isbn',
        'description',
        'publication_year',
        'publisher',
        'book_value',
        'author_id',
        'category_id',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'book_value' => 'decimal:2',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Author, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<BookCopy, $this> */
    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Every loan ever taken on any copy of this title. Loans hang off copies,
     * not off books, so demand for a title is only measurable through them.
     *
     * @return HasManyThrough<Loan, BookCopy, $this>
     */
    public function loans(): HasManyThrough
    {
        return $this->hasManyThrough(Loan::class, BookCopy::class);
    }
}
