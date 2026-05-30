# Design: Module 5 — Book CRUD

## Technical Approach

Implement Book entity following established Author/Category patterns. Two-phase delivery: (1) Model layer with migration, relationships, factory; (2) API layer with Actions, DTOs, FormRequests, Resource, Controller, routes, and exceptions. Uses contract pattern for future Loan dependency.

## Architecture Decisions

| Decision | Options Considered | Choice | Rationale |
|----------|-------------------|--------|-----------|
| `available_copies` stub | (a) Omit field (b) Stub as 0 (c) Throw if accessed | Stub as 0 via accessor | API shape complete now; Module 6 adds real logic |
| `popularity` sort | (a) Error on sort (b) No-op (c) LEFT JOIN | No-op — accept param, ignore | Zero loans exist; no error, no complexity |
| `available_only` filter | (a) Error (b) No-op | No-op — accept param, ignore | Graceful degradation until Module 6 |
| ISBN uniqueness | (a) DB only (b) FormRequest only (c) Both | Both: validation + constraint | Validation = UX, constraint = integrity |
| Loan check | (a) Direct Loan dep (b) Interface/null impl | LoanChecker interface | Decouples from non-existent Loan model |

## Data Flow

```
Request → FormRequest (validate) → Controller → Action → Model → DB
                                       ↓
                                    DTO (data transfer)
                                       ↓
              LoanChecker ←── DeleteBookAction (checks active loans)
```

## File Changes

### PR #1 — Model Layer (~120 lines)

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/{ts}_create_books_table.php` | Create | Books table with FKs, indexes |
| `app/Models/Book.php` | Create | Model with relationships, accessor |
| `database/factories/BookFactory.php` | Create | Factory using Author/Category |
| `app/Models/Author.php` | Modify | Add `books()` hasMany |
| `app/Models/Category.php` | Modify | Add `books()` hasMany |

### PR #2 — API Layer (~350 lines)

| File | Action | Description |
|------|--------|-------------|
| `app/Contracts/LoanChecker.php` | Create | Interface for loan checking |
| `app/Contracts/NullLoanChecker.php` | Create | Null implementation |
| `app/Exceptions/Catalog/DuplicateIsbnException.php` | Create | ISBN conflict exception |
| `app/Exceptions/Catalog/BookHasActiveLoansException.php` | Create | Active loans exception |
| `app/DTOs/Catalog/CreateBookDTO.php` | Create | Create book data transfer |
| `app/DTOs/Catalog/UpdateBookDTO.php` | Create | Update book data transfer |
| `app/Actions/Catalog/CreateBookAction.php` | Create | Create book action |
| `app/Actions/Catalog/UpdateBookAction.php` | Create | Update book action |
| `app/Actions/Catalog/DeleteBookAction.php` | Create | Delete book action |
| `app/Http/Requests/Api/V1/Catalog/CreateBookRequest.php` | Create | Create validation |
| `app/Http/Requests/Api/V1/Catalog/UpdateBookRequest.php` | Create | Update validation |
| `app/Http/Resources/Api/V1/Catalog/BookResource.php` | Create | JSON:API resource |
| `app/Http/Controllers/Api/V1/Catalog/BookController.php` | Create | CRUD controller |
| `routes/api/v1.php` | Modify | Add book routes |
| `app/Providers/AppServiceProvider.php` | Modify | Bind LoanChecker |
| `tests/Unit/Models/BookTest.php` | Create | Model unit tests |
| `tests/Unit/Actions/Catalog/CreateBookActionTest.php` | Create | Create action tests |
| `tests/Unit/Actions/Catalog/DeleteBookActionTest.php` | Create | Delete action tests |
| `tests/Feature/Http/Controllers/Api/V1/Catalog/BookControllerTest.php` | Create | Feature tests |

## Interfaces / Contracts

### Migration: `database/migrations/{timestamp}_create_books_table.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('isbn', 17)->unique();
            $table->year('publication_year');
            $table->decimal('book_value', 10, 2)->default(0);
            $table->foreignUlid('author_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignUlid('category_id')
                ->constrained()
                ->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('author_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
```

### Model: `app/Models/Book.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property-read int $available_copies
 */
final class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'title',
        'isbn',
        'publication_year',
        'book_value',
        'author_id',
        'category_id',
    ];

    /** @return array<string, string> */
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

    /** @return Attribute<int, never> */
    protected function availableCopies(): Attribute
    {
        return Attribute::get(fn (): int => 0); // Stub until Module 6
    }
}
```

### Factory: `database/factories/BookFactory.php`

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Book> */
final class BookFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'isbn' => fake()->unique()->isbn13(),
            'publication_year' => fake()->year(),
            'book_value' => fake()->randomFloat(2, 5, 100),
            'author_id' => Author::factory(),
            'category_id' => Category::factory(),
        ];
    }
}
```

### Author/Category Relationship Updates

```php
// app/Models/Author.php — add method
/** @return HasMany<Book, $this> */
public function books(): HasMany
{
    return $this->hasMany(Book::class);
}

// app/Models/Category.php — add method
/** @return HasMany<Book, $this> */
public function books(): HasMany
{
    return $this->hasMany(Book::class);
}
```

### Interface: `app/Contracts/LoanChecker.php`

```php
<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Book;

interface LoanChecker
{
    public function hasActiveLoans(Book $book): bool;
}
```

### Null Implementation: `app/Contracts/NullLoanChecker.php`

```php
<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Book;

final class NullLoanChecker implements LoanChecker
{
    public function hasActiveLoans(Book $book): bool
    {
        return false;
    }
}
```

### Exception: `app/Exceptions/Catalog/DuplicateIsbnException.php`

```php
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
```

### Exception: `app/Exceptions/Catalog/BookHasActiveLoansException.php`

```php
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
```

### DTO: `app/DTOs/Catalog/CreateBookDTO.php`

```php
<?php

declare(strict_types=1);

namespace App\DTOs\Catalog;

final readonly class CreateBookDTO
{
    public function __construct(
        public string $title,
        public string $isbn,
        public int $publication_year,
        public string $book_value,
        public string $author_id,
        public string $category_id,
    ) {}
}
```

### DTO: `app/DTOs/Catalog/UpdateBookDTO.php`

```php
<?php

declare(strict_types=1);

namespace App\DTOs\Catalog;

final readonly class UpdateBookDTO
{
    public function __construct(
        public ?string $title,
        public ?string $isbn,
        public ?int $publication_year,
        public ?string $book_value,
        public ?string $author_id,
        public ?string $category_id,
    ) {}
}
```

### Action: `app/Actions/Catalog/CreateBookAction.php`

```php
<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\DTOs\Catalog\CreateBookDTO;
use App\Exceptions\Catalog\DuplicateIsbnException;
use App\Models\Book;
use Illuminate\Database\QueryException;

final class CreateBookAction
{
    public function execute(CreateBookDTO $data): Book
    {
        try {
            return Book::query()->create([
                'title' => $data->title,
                'isbn' => $data->isbn,
                'publication_year' => $data->publication_year,
                'book_value' => $data->book_value,
                'author_id' => $data->author_id,
                'category_id' => $data->category_id,
            ]);
        } catch (QueryException $e) {
            if ($e->errorInfo[1] === 1062 || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                throw new DuplicateIsbnException($data->isbn);
            }
            throw $e;
        }
    }
}
```

### Action: `app/Actions/Catalog/UpdateBookAction.php`

```php
<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\DTOs\Catalog\UpdateBookDTO;
use App\Exceptions\Catalog\DuplicateIsbnException;
use App\Models\Book;
use Illuminate\Database\QueryException;

final class UpdateBookAction
{
    public function execute(Book $book, UpdateBookDTO $data): Book
    {
        $attributes = array_filter([
            'title' => $data->title,
            'isbn' => $data->isbn,
            'publication_year' => $data->publication_year,
            'book_value' => $data->book_value,
            'author_id' => $data->author_id,
            'category_id' => $data->category_id,
        ], fn ($value) => $value !== null);

        try {
            $book->update($attributes);
        } catch (QueryException $e) {
            if ($e->errorInfo[1] === 1062 || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                throw new DuplicateIsbnException($data->isbn ?? $book->isbn);
            }
            throw $e;
        }

        return $book;
    }
}
```

### Action: `app/Actions/Catalog/DeleteBookAction.php`

```php
<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Contracts\LoanChecker;
use App\Exceptions\Catalog\BookHasActiveLoansException;
use App\Models\Book;

final class DeleteBookAction
{
    public function __construct(
        private readonly LoanChecker $loanChecker,
    ) {}

    public function execute(Book $book): void
    {
        if ($this->loanChecker->hasActiveLoans($book)) {
            throw new BookHasActiveLoansException($book->id);
        }

        $book->delete();
    }
}
```

### FormRequest: `app/Http/Requests/Api/V1/Catalog/CreateBookRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Catalog;

use App\DTOs\Catalog\CreateBookDTO;
use Illuminate\Foundation\Http\FormRequest;

final class CreateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'max:17', 'unique:books,isbn'],
            'publication_year' => ['required', 'integer', 'min:1000', 'max:'.date('Y')],
            'book_value' => ['required', 'numeric', 'min:0'],
            'author_id' => ['required', 'string', 'exists:authors,id'],
            'category_id' => ['required', 'string', 'exists:categories,id'],
        ];
    }

    public function toDto(): CreateBookDTO
    {
        return new CreateBookDTO(
            title: $this->string('title')->toString(),
            isbn: $this->string('isbn')->toString(),
            publication_year: $this->integer('publication_year'),
            book_value: $this->string('book_value')->toString(),
            author_id: $this->string('author_id')->toString(),
            category_id: $this->string('category_id')->toString(),
        );
    }
}
```

### FormRequest: `app/Http/Requests/Api/V1/Catalog/UpdateBookRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Catalog;

use App\DTOs\Catalog\UpdateBookDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'isbn' => ['sometimes', 'string', 'max:17', Rule::unique('books', 'isbn')->ignore($this->route('book'))],
            'publication_year' => ['sometimes', 'integer', 'min:1000', 'max:'.date('Y')],
            'book_value' => ['sometimes', 'numeric', 'min:0'],
            'author_id' => ['sometimes', 'string', 'exists:authors,id'],
            'category_id' => ['sometimes', 'string', 'exists:categories,id'],
        ];
    }

    public function toDto(): UpdateBookDTO
    {
        return new UpdateBookDTO(
            title: $this->filled('title') ? $this->string('title')->toString() : null,
            isbn: $this->filled('isbn') ? $this->string('isbn')->toString() : null,
            publication_year: $this->filled('publication_year') ? $this->integer('publication_year') : null,
            book_value: $this->filled('book_value') ? $this->string('book_value')->toString() : null,
            author_id: $this->filled('author_id') ? $this->string('author_id')->toString() : null,
            category_id: $this->filled('category_id') ? $this->string('category_id')->toString() : null,
        );
    }
}
```

### Resource: `app/Http/Resources/Api/V1/Catalog/BookResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Catalog;

use App\Models\Book;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/** @property-read Book $resource */
final class BookResource extends JsonApiResource
{
    /** @var list<string> */
    public array $attributes = [
        'title',
        'isbn',
        'publication_year',
        'book_value',
        'available_copies',
    ];

    /** @var list<string> */
    public array $relationships = [
        'author',
        'category',
    ];
}
```

### Controller: `app/Http/Controllers/Api/V1/Catalog/BookController.php`

```php
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
            ->allowedFilters([
                AllowedFilter::exact('author_id'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::callback('search', function ($query, $value): void {
                    $query->where(function ($q) use ($value): void {
                        $q->where('title', 'like', "%{$value}%")
                          ->orWhere('isbn', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('available_only', fn ($query, $value) => $query), // No-op
            ])
            ->allowedSorts([
                AllowedSort::field('title'),
                AllowedSort::field('publication_year'),
                AllowedSort::field('created_at'),
                AllowedSort::callback('popularity', fn ($query, $descending) => $query), // No-op
            ])
            ->allowedIncludes(['author', 'category'])
            ->defaultSort('title')
            ->paginate();

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
```

### Routes: `routes/api/v1.php` (additions)

```php
use App\Http\Controllers\Api\V1\Catalog\BookController;

Route::prefix('books')->name('books.')->group(function (): void {
    Route::get('/', [BookController::class, 'index'])->name('index');
    Route::get('/{book}', [BookController::class, 'show'])->name('show');
    Route::middleware(['auth:sanctum', 'role:librarian,admin', 'throttle:api'])->group(function (): void {
        Route::post('/', [BookController::class, 'store'])->name('store');
        Route::put('/{book}', [BookController::class, 'update'])->name('update');
        Route::delete('/{book}', [BookController::class, 'destroy'])->name('destroy');
    });
});
```

### AppServiceProvider Binding (addition to boot method)

```php
use App\Contracts\LoanChecker;
use App\Contracts\NullLoanChecker;

// In register() method:
$this->app->bind(LoanChecker::class, NullLoanChecker::class);
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | Book model attributes, relationships, accessor | `tests/Unit/Models/BookTest.php` |
| Unit | CreateBookAction with valid data, duplicate ISBN | `tests/Unit/Actions/Catalog/CreateBookActionTest.php` |
| Unit | DeleteBookAction with/without active loans | `tests/Unit/Actions/Catalog/DeleteBookActionTest.php` |
| Feature | List, show, create, update, delete endpoints | `tests/Feature/Http/Controllers/Api/V1/Catalog/BookControllerTest.php` |
| Feature | Query filters, sorting, includes | Same file |
| Feature | Authorization (roles, unauthenticated) | Same file |

## Migration / Rollout

No data migration required. New entity with no existing data.

**Rollout order:**
1. PR #1: Migration → Model → Factory → Relationships
2. PR #2: Contracts → Exceptions → DTOs → Actions → Requests → Resource → Controller → Routes → Service binding → Tests

## Open Questions

None — all decisions resolved per confirmed D2 and D5.
