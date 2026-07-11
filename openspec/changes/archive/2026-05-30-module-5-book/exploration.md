# Exploration: Module 5 — Book

## Current State

The library API already establishes strong patterns for catalog management:

### Established Patterns

1. **Models**: All models use `HasUlids`, `HasFactory`, and are `final`. Properties are PHPDoc-typed with `CarbonInterface` for timestamps.
2. **Actions**: Single-responsibility classes under `app/Actions/Catalog/`. Each action has one `execute()` method. No dependency injection framework — dependencies are constructor-injected as typed parameters.
3. **DTOs**: Readonly classes under `app/DTOs/Catalog/` with constructor property promotion. Hydrated via `toDto()` from FormRequests.
4. **Form Requests**: Under `app/Http/Requests/Api/V1/Catalog/`. Override `authorize()` (delegates to middleware), implement `rules()`, provide `toDto()` method.
5. **Resources**: Extend `JsonApiResource`. Define `$attributes` and `$relationships` arrays. Located under `app/Http/Resources/Api/V1/Catalog/`.
6. **Controllers**: Under `app/Http/Controllers/Api/V1/Catalog/`. Use trait `ApiResponse` for uniform JSON responses. Actions are constructor-injected.
7. **Exceptions**: Extend `RuntimeException`, located under `app/Exceptions/{Domain}/`. Handled centrally in `ApiExceptionHandler::render()`.
8. **Routing**: Defined in `routes/api/v1.php`. Middleware grouped (`auth:sanctum`, `role:librarian,admin`, `throttle:api`). Uses `name()` and resource prefixes.
9. **Testing**: Pest 4. Feature tests in `tests/Feature/Http/Controllers/Api/V1/Catalog/`. No unit test files for actions (rely on feature tests). Coverage threshold 100%.
10. **Code style**: `declare(strict_types=1)` on every file, final classes, typed properties, PHPDoc blocks.

### Dependency: spatie/laravel-query-builder

✅ **CONFIRMED**: spatie/laravel-query-builder v7.1 is already in composer.json. Ready to use immediately.

## Affected Areas

- `app/Models/Book.php` — New model, will have relationships to Author, Category, BookCopy, Reservation
- `app/Models/Author.php` — Update: add `hasMany('books')`
- `app/Models/Category.php` — Update: add `hasMany('books')`
- `app/Actions/Catalog/CreateBookAction.php` — New
- `app/Actions/Catalog/UpdateBookAction.php` — New
- `app/Actions/Catalog/DeleteBookAction.php` — New
- `app/DTOs/Catalog/CreateBookDTO.php` — New
- `app/DTOs/Catalog/UpdateBookDTO.php` — New
- `app/Http/Requests/Api/V1/Catalog/CreateBookRequest.php` — New
- `app/Http/Requests/Api/V1/Catalog/UpdateBookRequest.php` — New
- `app/Http/Resources/Api/V1/Catalog/BookResource.php` — New
- `app/Exceptions/Catalog/DuplicateIsbnException.php` — New
- `app/Exceptions/Catalog/BookHasActiveLoansException.php` — New
- `app/Http/Controllers/Api/V1/Catalog/BookController.php` — New
- `routes/api/v1.php` — Add Book routes (GET, POST, PUT, DELETE)
- `database/migrations/YYYY_MM_DD_xxxxxx_create_books_table.php` — New
- `database/factories/BookFactory.php` — New
- `tests/Unit/Models/BookTest.php` — New
- `tests/Feature/Http/Controllers/Api/V1/Catalog/BookControllerTest.php` — New

## Critical Decisions & Risks

### Risk 1: `available_copies` Count Calculation (Module 5.2)
**Problem**: The spec requires `BookResource` to include `available_copies` (count), but `BookCopy` table doesn't exist until Module 6.

**Options**:
1. **Defer count to Module 6**: Create the resource property now but leave it `null` until BookCopy table exists. Update resource in Module 6.
2. **Stub with 0**: Always return `0` for now. Module 6 replaces the calculation with real logic.
3. **Use query-time relationship loading**: Create a `BookCopy` model stub (with migration but no records yet) and use `withCount('bookCopies')` even if data is empty.

**Recommendation**: **Option 1 — Defer count to Module 6**. 
- Rationale: The `BookResource` should be complete by Module 5.2, but the count calculation is a Module 6 responsibility. Define `available_copies` as a computed property that queries `BookCopy::where('book_id', $this->id)->count()`, and leave it stubbed with `0` for now. In Module 6, when BookCopy exists, the query will return real data.
- Implementation: Add method to Book model:
  ```php
  public function getAvailableCopiesAttribute(): int {
      return $this->bookCopies()->count() ?? 0; // Module 6 populates this
  }
  ```
  Add to resource `$attributes = ['available_copies']`.

### Risk 2: Sorting by `popularity` (Module 5.2)
**Problem**: Spec requires `?sort_by=popularity` but Loan table doesn't exist until Module 7+.

**Options**:
1. **Ignore unknown sorts**: Default to `title` if `popularity` is requested. Silently ignore invalid sorts.
2. **Raise exception**: Throw `InvalidSortException` if unknown sort key is requested.
3. **Stub the column**: Create a `popularity_score` column in books_table (nullable), calculate in Module 7+ when Loans exist.

**Recommendation**: **Option 1 — Ignore unknown sorts with a note**.
- Rationale: spatie/laravel-query-builder silently drops unknown `AllowedSort` keys by default. Accept `popularity` as valid but document that calculation is deferred to Module 7 (when Loan exists).
- Implementation: In BookController, allow the sort key:
  ```php
  AllowedSort::field('popularity', 'popularity_score'),
  ```
  Leave `popularity_score` nullable in migration. In Module 7, backfill with loan counts.

### Risk 3: `available_only` Filter (Module 5.2)
**Problem**: Spec requires `?filters[available_only]=true` but depends on BookCopy existence (Module 6).

**Options**:
1. **Ignore the filter now**: Don't implement `available_only` until Module 6.
2. **Assume all books are available**: If BookCopy doesn't exist, treat as always available (return all).
3. **Create BookCopy stub table**: Add migration in Module 5, but populate in Module 6.

**Recommendation**: **Option 2 — Assume all books available, update in Module 6**.
- Rationale: API should gracefully degrade. If `available_only=true`, don't filter (return all) because BookCopy doesn't exist yet. In Module 6, add the actual filter logic.
- Implementation: In BookController, add conditional logic:
  ```php
  if ($request->boolean('filters.available_only') && Schema::hasTable('book_copies')) {
      // Filter to available copies
  }
  ```

### Risk 4: ISBN Uniqueness Constraint
**Problem**: Spec requires `unique` constraint on ISBN, raising `DuplicateIsbnException` on violation.

**Options**:
1. **Database constraint**: Add `unique` to migration. Handle `IntegrityConstraintViolationException` in action and throw domain exception.
2. **Validation-only**: Use `unique:books,isbn` in FormRequest. No need for action-level handling.
3. **Dual approach**: Both validation + database constraint for defense-in-depth.

**Recommendation**: **Option 3 — Dual approach (validation + constraint)**.
- Rationale: Validation catches user errors early; database constraint prevents race conditions. Both are needed.
- Implementation:
  - Migration: `$table->string('isbn')->unique();`
  - FormRequest: `'isbn' => ['required', 'string', 'unique:books,isbn']`
  - Action: Catch `IntegrityConstraintViolationException`, throw `DuplicateIsbnException`

### Risk 5: Foreign Key Constraints (Author, Category)
**Problem**: Spec requires `RESTRICT` on both Author FK and Category FK. Deletion of Author/Category should fail if books exist.

**Options**:
1. **RESTRICT (default behavior)**: Database raises exception on delete. Handle in exception handler.
2. **CASCADE**: Automatically delete books. **NOT recommended** — violates spec.
3. **SET NULL**: Allow delete, orphan books. **NOT recommended** — spec says RESTRICT.

**Recommendation**: **RESTRICT with explicit exception handling**.
- Rationale: Matches spec. Prevents accidental data loss. Clear error message.
- Implementation:
  - Migration: `foreignId('author_id')->constrained('authors')->restrictOnDelete();`
  - Migration: `foreignId('category_id')->constrained('categories')->restrictOnDelete();`
  - Exception handler: Catch `QueryException` with constraint error, map to domain exception (e.g., `AuthorHasAssociatedBooksException`)

## Approaches

### Approach A: Implement Full Module 5.1 + 5.2 in Single PR
- **Pros**: Complete feature, no fragmentation, easier to review as a unit
- **Cons**: Large PR (migration + model + 3 actions + 2 DTOs + 2 requests + 1 resource + 2 exceptions + controller + routes + ~150 lines of tests)
- **Effort**: High
- **Chained PR Risk**: YES — likely exceeds 400-line budget

### Approach B: Split into Two PRs
**PR #1**: Module 5.1 (Model, Migration, Factory, Relationships)
- Files: migration, Book model, BookFactory, Author/Category updates
- Lines: ~100
- Tests: ~30 lines (unit + model structure)

**PR #2**: Module 5.2 (API — Actions, DTOs, Requests, Resource, Controller, Routes)
- Files: 3 actions, 2 DTOs, 2 requests, 1 resource, 2 exceptions, controller, routes
- Lines: ~250–300
- Tests: ~200 lines (feature tests for all CRUD operations)

- **Pros**: Reviewable chunks, can merge 5.1 independently, 5.2 features depend on 5.1 anyway
- **Cons**: Two merges, two CI runs
- **Effort**: Medium
- **Chained PR Risk**: NO — each PR stays under 400 lines

## Recommendation

**Use Approach B — Split into Two PRs:**

1. **PR #1 (Module 5.1)**: Foundations (migration, model, factories, relationships)
   - No API endpoints yet
   - Allows parallel work on Module 6 if needed
   - Small, focused, easy to review

2. **PR #2 (Module 5.2)**: API complete (actions, resources, controller, routes, exception handling)
   - Depends on 5.1 being merged
   - Uses spatie/laravel-query-builder for filtering/sorting
   - Handles deferred concerns (popularity, available_copies) gracefully

**Why**: Each PR is defensible, reviewable, and testable independently. Keeps cognitive load on reviewers low. Aligns with strict TDD mode (tests written first in both PRs).

## Key Patterns to Follow

1. **Model**: 
   ```php
   final class Book extends Model {
       use HasUlids, HasFactory;
       protected $fillable = [...];
       public function casts(): array { ... }
   }
   ```

2. **Action**:
   ```php
   final class CreateBookAction {
       public function execute(CreateBookDTO $data): Book {
           return Book::query()->create([...]);
       }
   }
   ```

3. **DTO**:
   ```php
   final readonly class CreateBookDTO {
       public function __construct(
           public string $title,
           public string $isbn,
           // ...
       ) {}
   }
   ```

4. **FormRequest**:
   ```php
   public function toDto(): CreateBookDTO {
       return new CreateBookDTO(
           title: $this->string('title')->toString(),
           // ...
       );
   }
   ```

5. **Resource**:
   ```php
   final class BookResource extends JsonApiResource {
       public array $attributes = ['title', 'isbn', 'available_copies'];
       public array $relationships = ['author', 'category'];
   }
   ```

6. **Exception**:
   ```php
   final class DuplicateIsbnException extends RuntimeException {
       public function __construct() {
           parent::__construct('ISBN already exists.');
       }
   }
   ```

7. **Test**:
   ```php
   it('creates a book with valid data', function(): void {
       $librarian = User::factory()->create(['role' => UserRole::Librarian]);
       Sanctum::actingAs($librarian);
       
       $this->postJson('/api/v1/books', [
           'title' => 'The Great Gatsby',
           'isbn' => '978-0-7432-7356-5',
           // ...
       ])->assertCreated();
   });
   ```

## Deferred Concerns

- **`available_copies` calculation**: Requires BookCopy table (Module 6). Stub with `0` for now.
- **`popularity` sorting**: Requires Loan table and aggregation logic (Module 7+). Stub with nullable `popularity_score` column.
- **`available_only` filter**: Requires BookCopy table (Module 6). Silently ignored for now.
- **Cascade/soft delete behavior**: Not specified in Module 5. Books are `SoftDeletes` per spec; implement in Module 5.1.

## Ready for Proposal

**Yes** — All critical decisions documented, risk mitigations clear, patterns confirmed in codebase. Recommend starting with sdd-propose phase to solidify intent and scope.

**Next phase**: sdd-propose (define change proposal with rollback plan, then sdd-spec for detailed requirements).
