<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $title
 * @property string $isbn
 * @property int $publication_year
 * @property string $book_value
 * @property string $author_id
 * @property string $category_id
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Author $author
 * @property-read Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BookCopy> $copies
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
        'publication_year',
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
}
