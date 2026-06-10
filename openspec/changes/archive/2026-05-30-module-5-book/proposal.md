# Proposal: Module 5 — Book CRUD

## Intent

Implement complete Book management for the library catalog, enabling librarians and admins to create, read, update, and delete books with full metadata (title, ISBN, publication year, author, category). This addresses issue #6 and fulfills user stories US-007, US-008, and US-009. Books are foundational to the lending workflow — Modules 6–8 depend on this entity.

## Scope

### In Scope
- Book model with ULIDs, SoftDeletes, and relationships to Author/Category
- Migration with ISBN uniqueness constraint and FK restrictions
- BookFactory for testing
- CRUD actions (Create/Update/Delete) following existing Action pattern
- DTOs for Create and Update operations
- Form Requests with validation including ISBN uniqueness
- BookResource (JSON:API format) with stubbed `available_copies`
- BookController with spatie/laravel-query-builder for filtering/sorting
- Domain exceptions: `DuplicateIsbnException`, `BookHasActiveLoansException`
- API routes under `/api/v1/books` with role-based authorization
- Full test coverage (Pest 4) for model and API endpoints

### Out of Scope
- BookCopy model/table (Module 6)
- Reservation system (Module 6)
- Loan/Return functionality (Module 7–8)
- Fine calculation (Module 8+)
- Real `available_copies` calculation (requires BookCopy — Module 6)
- Real `popularity` sorting (requires Loan aggregation — Module 7+)
- Real `available_only` filter (requires BookCopy — Module 6)

## Capabilities

### New Capabilities
- `book-catalog`: Book entity CRUD, ISBN validation, author/category relationships, query filtering/sorting

### Modified Capabilities
None — this is the first capability in `openspec/specs/`

## Approach

Two-PR delivery following exploration analysis:

**PR #1 (5.1 — Model Layer)**: Migration, Book model, BookFactory, Author/Category relationship updates.

**PR #2 (5.2 — API Layer)**: Actions, DTOs, FormRequests, BookResource, exceptions, BookController, routes, full feature tests.

Key technical decisions:
1. `available_copies` — stubbed as `0` via accessor; real calculation deferred to Module 6
2. `popularity` sort — define `AllowedSort::field('popularity')` now, returns empty result (no loans yet)
3. `available_only` filter — accept parameter but no-op until Module 6
4. ISBN uniqueness — dual approach: FormRequest validation + DB unique constraint
5. DeleteBookAction — uses interface/contract pattern to check active loans without hard dependency on Loan model

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `database/migrations/` | New | `create_books_table` with FKs to authors/categories |
| `app/Models/Book.php` | New | Model with ULIDs, SoftDeletes, relationships |
| `app/Models/Author.php` | Modified | Add `books()` hasMany relationship |
| `app/Models/Category.php` | Modified | Add `books()` hasMany relationship |
| `database/factories/BookFactory.php` | New | Factory for testing |
| `app/Actions/Catalog/` | New | Create/Update/DeleteBookAction |
| `app/DTOs/Catalog/` | New | CreateBookDTO, UpdateBookDTO |
| `app/Http/Requests/Api/V1/Catalog/` | New | CreateBookRequest, UpdateBookRequest |
| `app/Http/Resources/Api/V1/Catalog/BookResource.php` | New | JSON:API resource |
| `app/Exceptions/Catalog/` | New | DuplicateIsbnException, BookHasActiveLoansException |
| `app/Http/Controllers/Api/V1/Catalog/BookController.php` | New | CRUD controller |
| `routes/api/v1.php` | Modified | Add Book routes |
| `tests/Feature/Http/Controllers/Api/V1/Catalog/BookControllerTest.php` | New | Feature tests |
| `tests/Unit/Models/BookTest.php` | New | Model unit tests |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| ISBN uniqueness race condition | Low | Dual validation (FormRequest + DB constraint) |
| `popularity` sort breaks without Loan table | Medium | Use `AllowedSort` with graceful fallback (empty sort) |
| `available_copies` confusion in API response | Low | Document as "always 0 until Module 6" in resource |
| DeleteBookAction depends on Loan model (Module 8) | High | Use contract/interface pattern (see Key Decisions) |
| FK RESTRICT blocks Author/Category deletion | Low | Expected behavior per spec; clear error messages |

## Key Decisions

### D1: `available_copies` Stub
**Decision**: Return `0` from `Book::getAvailableCopiesAttribute()` until BookCopy exists.
**Rationale**: API shape is complete; calculation is Module 6 responsibility. No breaking changes when real logic lands.

### D2: `popularity` Sort Handling
**Decision**: Accept `popularity` as `AllowedSort`, but since Loan table doesn't exist, return results in default order.
**Rationale**: spatie/laravel-query-builder handles missing columns gracefully. Alternative (LEFT JOIN with fallback) adds complexity for zero benefit until Module 7.

### D3: `available_only` Filter
**Decision**: Accept filter parameter but no-op (return all books) until Module 6.
**Rationale**: Graceful degradation. API consumers see consistent parameter names; behavior changes when BookCopy exists.

### D4: ISBN Uniqueness
**Decision**: Dual approach — FormRequest `unique:books,isbn` rule + DB `UNIQUE` constraint.
**Rationale**: Validation catches 99% of cases with user-friendly errors. DB constraint prevents race conditions. Action catches `IntegrityConstraintViolationException` and throws `DuplicateIsbnException`.

### D5: DeleteBookAction and Loan Dependency
**Decision**: Use contract pattern — `DeleteBookAction` depends on `App\Contracts\LoanChecker` interface with method `hasActiveLoans(Book $book): bool`. In Module 5, provide a null implementation that always returns `false`. Module 8 swaps in real implementation.
**Rationale**: Avoids coupling to non-existent Loan model. Interface is cheap, swappable, testable. Exception `BookHasActiveLoansException` is defined now, thrown when `LoanChecker::hasActiveLoans()` returns true.

## Rollback Plan

### PR #1 (Model Layer)
1. Revert migration: `php artisan migrate:rollback --step=1`
2. Remove `Book.php`, `BookFactory.php`
3. Revert Author.php and Category.php relationship additions
4. Verify: `php artisan migrate:fresh --seed && composer test`

### PR #2 (API Layer)
1. Remove routes from `routes/api/v1.php`
2. Delete all Book-related files in Actions/, DTOs/, Requests/, Resources/, Exceptions/, Controllers/
3. Delete test files
4. Verify: `composer test` (PR #1 model layer remains intact)

## Dependencies

- **Author model**: Must exist with factory (confirmed ✅)
- **Category model**: Must exist with factory (confirmed ✅)
- **spatie/laravel-query-builder**: v7.1 confirmed ✅
- **Sanctum auth**: Role-based middleware exists ✅

## Delivery Plan

| PR | Scope | Est. Lines | Review Risk |
|----|-------|------------|-------------|
| #1 (5.1) | Model, migration, factory, relationships | ~120 | Low |
| #2 (5.2) | API (actions, DTOs, requests, resource, controller, routes, exceptions, tests) | ~350 | Medium |

**Total**: ~470 lines across two PRs, each under 400-line budget.

## Success Criteria

- [ ] `POST /api/v1/books` creates a book with valid author_id, category_id, and unique ISBN
- [ ] `GET /api/v1/books` returns paginated list with JSON:API format
- [ ] `GET /api/v1/books?filter[author_id]=...` filters by author
- [ ] `GET /api/v1/books?filter[category_id]=...` filters by category
- [ ] `GET /api/v1/books?filter[search]=...` searches by title/ISBN
- [ ] `GET /api/v1/books?sort=title,-publication_year` sorts correctly
- [ ] `GET /api/v1/books/{id}` returns single book with relationships
- [ ] `PUT /api/v1/books/{id}` updates book fields
- [ ] `DELETE /api/v1/books/{id}` soft-deletes book (when no active loans)
- [ ] `DuplicateIsbnException` thrown on duplicate ISBN
- [ ] `BookHasActiveLoansException` defined (behavior activates in Module 8)
- [ ] 100% test coverage maintained
- [ ] PHPStan level 8 passes
- [ ] Pint formatting clean
