# Tasks: Module 5 — Book CRUD

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 750-850 lines |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR #1: Model Layer (~150 lines) → PR #2: API Layer (~650 lines) |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Book Model + Migration + Relationships | PR #1 | Base to main; includes tests, factory, model relationships |
| 2 | Complete API Layer with TDD | PR #2 | Base to main; includes all actions, requests, controller, routes, tests |

---

## PR #1 — Module 5.1: Model Layer (~150 lines)

### Phase 1: Foundation (Model + Migration)

- [x] **T1.1** Write unit tests in `tests/Unit/Models/BookTest.php` — test fillable attributes, casts, relationships (author, category), availableCopies accessor returns 0, soft deletes
- [x] **T1.2** Create migration `database/migrations/2026_05_30_035742_create_books_table.php` — books table with ulid id, title, isbn (unique), publication_year, book_value, author_id FK, category_id FK, timestamps, soft deletes, indexes on author_id/category_id
- [x] **T1.3** Create `app/Models/Book.php` — HasUlids, SoftDeletes, fillable, casts, belongsTo author/category, availableCopies accessor stub (returns 0)
- [x] **T1.4** Create `database/factories/BookFactory.php` — definition with faker for title, unique isbn13, year, book_value, Author::factory(), Category::factory()
- [x] **T1.5** Run `php artisan test --compact --filter=BookTest` — 6 passed, 2 skipped (copies/reservations deferred to Module 6)
- [x] **T1.6** Run migration `php artisan migrate` — books table created successfully

### Phase 2: Relationships

- [x] **T2.1** Update `app/Models/Author.php` — add `books(): HasMany` method with PHPDoc return type
- [x] **T2.2** Update `app/Models/Category.php` — add `books(): HasMany` method with PHPDoc return type
- [x] **T2.3** Run `php artisan test --compact` — 184 passed, 2 skipped, 0 failed
- [x] **T2.4** Run `vendor/bin/pint --dirty --format agent` — fixed fully_qualified_strict_types in Book.php

---

## PR #2 — Module 5.2: API Layer (~650 lines)

### Phase 1: Tests First (TDD RED)

- [ ] **T3.1** Write `tests/Unit/Actions/Catalog/CreateBookActionTest.php` — test execute creates book, DuplicateIsbnException on duplicate ISBN
- [ ] **T3.2** Write `tests/Unit/Actions/Catalog/DeleteBookActionTest.php` — test execute deletes when no active loans, throws BookHasActiveLoansException when hasActiveLoans=true
- [ ] **T3.3** Write `tests/Feature/Http/Controllers/Api/V1/Catalog/BookControllerTest.php` (index) — test paginated listing, search filter (title/isbn), author_id/category_id filters, sort by title/publication_year/created_at, popularity/available_only no-ops, includes author/category
- [ ] **T3.4** Write `tests/Feature/Http/Controllers/Api/V1/Catalog/BookControllerTest.php` (show) — test existing book returns 200 with resource, non-existent returns 404
- [ ] **T3.5** Write `tests/Feature/Http/Controllers/Api/V1/Catalog/BookControllerTest.php` (store) — test librarian/admin create book 201, duplicate ISBN 409, validation 422, unauthenticated 401, user role 403
- [ ] **T3.6** Write `tests/Feature/Http/Controllers/Api/V1/Catalog/BookControllerTest.php` (update) — test librarian update 200, ISBN conflict 409, validation 422, unauthorized 403
- [ ] **T3.7** Write `tests/Feature/Http/Controllers/Api/V1/Catalog/BookControllerTest.php` (destroy) — test delete no loans 204, active loans 409, unauthorized 403
- [ ] **T3.8** Run `php artisan test --compact` — expect failures (RED step complete)

### Phase 2: Contracts & Exceptions (TDD GREEN — Foundation)

- [ ] **T4.1** Create `app/Contracts/LoanChecker.php` — interface with hasActiveLoans(Book $book): bool
- [ ] **T4.2** Create `app/Contracts/NullLoanChecker.php` — implements LoanChecker, hasActiveLoans always returns false
- [ ] **T4.3** Create `app/Exceptions/Catalog/DuplicateIsbnException.php` — constructor with isbn property, render returns JSON:API 409 with DUPLICATE_ISBN code
- [ ] **T4.4** Create `app/Exceptions/Catalog/BookHasActiveLoansException.php` — constructor with bookId property, render returns JSON:API 409 with BOOK_HAS_ACTIVE_LOANS code

### Phase 3: DTOs (TDD GREEN — Data Transfer)

- [ ] **T5.1** Create `app/DTOs/Catalog/CreateBookDTO.php` — readonly constructor with title, isbn, publication_year, book_value, author_id, category_id
- [ ] **T5.2** Create `app/DTOs/Catalog/UpdateBookDTO.php` — readonly constructor with nullable title, isbn, publication_year, book_value, author_id, category_id

### Phase 4: Actions (TDD GREEN — Business Logic)

- [ ] **T6.1** Create `app/Actions/Catalog/CreateBookAction.php` — execute(CreateBookDTO): Book, catch QueryException for duplicate ISBN (1062 or UNIQUE constraint), throw DuplicateIsbnException
- [ ] **T6.2** Create `app/Actions/Catalog/UpdateBookAction.php` — execute(Book, UpdateBookDTO): Book, array_filter nulls, catch QueryException, throw DuplicateIsbnException
- [ ] **T6.3** Create `app/Actions/Catalog/DeleteBookAction.php` — constructor inject LoanChecker, execute(Book): void, check hasActiveLoans, throw BookHasActiveLoansException or delete

### Phase 5: Requests (TDD GREEN — Validation)

- [ ] **T7.1** Create `app/Http/Requests/Api/V1/Catalog/CreateBookRequest.php` — rules (required title/isbn/publication_year/book_value/author_id/category_id, unique isbn, exists checks), toDto() method
- [ ] **T7.2** Create `app/Http/Requests/Api/V1/Catalog/UpdateBookRequest.php` — rules (sometimes fields, unique isbn ignore current book, exists checks), toDto() with null handling

### Phase 6: Resource (TDD GREEN — Response Format)

- [ ] **T8.1** Create `app/Http/Resources/Api/V1/Catalog/BookResource.php` — extends JsonApiResource, attributes array (title, isbn, publication_year, book_value, available_copies), relationships array (author, category)

### Phase 7: Controller (TDD GREEN — HTTP Layer)

- [ ] **T9.1** Create `app/Http/Controllers/Api/V1/Catalog/BookController.php` — index with QueryBuilder (filters: author_id exact, category_id exact, search callback, available_only no-op; sorts: title, publication_year, created_at, popularity no-op; includes: author, category; defaultSort title; paginate)
- [ ] **T9.2** Add BookController::show — load author/category, return BookResource
- [ ] **T9.3** Add BookController::store — inject CreateBookAction, execute toDto, load relationships, return 201
- [ ] **T9.4** Add BookController::update — inject UpdateBookAction, execute with book + toDto, load relationships, return 200
- [ ] **T9.5** Add BookController::destroy — inject DeleteBookAction, execute, return 204

### Phase 8: Wiring (TDD GREEN — Integration)

- [ ] **T10.1** Update `routes/api/v1.php` — add books prefix/name group, public index/show routes, auth+role middleware for store/update/destroy
- [ ] **T10.2** Update `app/Providers/AppServiceProvider.php` — bind LoanChecker::class to NullLoanChecker::class in register method

### Phase 9: Verification (TDD GREEN — Prove It)

- [ ] **T11.1** Run `php artisan test --compact` — all tests must pass (GREEN step complete)
- [ ] **T11.2** Run `vendor/bin/pint --dirty --format agent` — format all modified files

---

## Task Dependencies

| Task | Depends On |
|------|-----------|
| T1.2-T1.4 | T1.1 (write tests first) |
| T1.5 | T1.2, T1.3, T1.4 |
| T2.3 | T2.1, T2.2 |
| T3.8 | T3.1-T3.7 (all tests written) |
| T4.1-T10.2 | T3.8 (RED step complete) |
| T11.1 | T4.1-T10.2 (all implementation complete) |

---

## Estimated Lines Per PR

| PR | Production Code | Tests | Total Changed Lines |
|----|----------------|-------|---------------------|
| PR #1 | ~120 | ~30 | ~150 |
| PR #2 | ~350 | ~300 | ~650 |
| **Total** | **~470** | **~330** | **~800** |

---

## TDD Workflow Summary

**PR #1**: Write unit tests → Create migration → Create model → Create factory → Update relationships → Verify tests pass → Format

**PR #2**: Write ALL feature/unit tests (RED) → Create contracts → Create exceptions → Create DTOs → Create actions → Create requests → Create resource → Create controller → Add routes → Bind service → Verify tests pass (GREEN) → Format

**Chain Strategy Decision Required**: User must choose between:
- **Stacked PRs to main** (fast iteration, independent merges)
- **Feature Branch Chain** (rollback control, coordinated release)
- **size:exception** (single PR with maintainer approval)
