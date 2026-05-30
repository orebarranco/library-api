# Book Catalog Specification

## Purpose

The Book Catalog capability enables librarians and admins to manage the library's book inventory with full CRUD operations, ISBN validation, author/category relationships, and public query filtering/sorting.

## Requirements

### Requirement: Book Entity Storage

The system MUST persist books with ULID primary keys, soft deletes, ISBN uniqueness, and foreign key relationships to authors and categories.

#### Scenario: Create book with valid relationships

- GIVEN an author and category exist
- WHEN a book is created with title, isbn, author_id, category_id, and optional metadata
- THEN the book is stored with ULID, timestamps, and all relationships intact

#### Scenario: ISBN uniqueness enforcement

- GIVEN a book with ISBN "978-0-123456-78-9" exists
- WHEN another book is created with the same ISBN
- THEN HTTP 422 is returned. On `store`, FormRequest catches it first (VALIDATION_ERROR). On `update`, the action throws DuplicateIsbnException (DUPLICATE_ISBN). The DB UNIQUE constraint is the final safety net in both cases.

#### Scenario: Soft delete preserves data

- GIVEN a book exists with no active loans
- WHEN the book is soft-deleted
- THEN deleted_at is set, the book is excluded from listings, but data remains in the database

### Requirement: Public Book Listing

The system MUST provide a public, paginated list of books with filtering, sorting, and search capabilities via spatie/laravel-query-builder.

#### Scenario: Paginated listing without authentication

- GIVEN books exist in the catalog
- WHEN GET /api/v1/books is requested without authentication
- THEN the system returns 15 books per page in JSON:API format with pagination metadata

#### Scenario: Search by title

- GIVEN books with titles "Clean Code" and "Dirty Deeds"
- WHEN GET /api/v1/books?search=Clean is requested
- THEN only "Clean Code" is returned

#### Scenario: Search by author name

- GIVEN a book by author "Martin Fowler"
- WHEN GET /api/v1/books?search=Fowler is requested
- THEN books by Martin Fowler are returned

#### Scenario: Filter by category

- GIVEN books in categories "Science Fiction" (id=1) and "History" (id=2)
- WHEN GET /api/v1/books?filters[category_id]=1 is requested
- THEN only Science Fiction books are returned

#### Scenario: Sort by title ascending

- GIVEN books "Zebra", "Alpha", "Beta"
- WHEN GET /api/v1/books?sort_by=title&sort_direction=asc is requested
- THEN books are returned in order: Alpha, Beta, Zebra

#### Scenario: Popularity sort stub

- GIVEN popularity sort is requested via ?sort_by=popularity
- WHEN the request is processed
- THEN the system MUST accept the parameter without error and return books in default order (no JOIN, no-op until Module 7)

#### Scenario: Available-only filter stub

- GIVEN available_only filter is requested via ?filters[available_only]=true
- WHEN the request is processed
- THEN the system MUST accept the parameter and return all books (no-op until Module 6)

### Requirement: Book Detail Retrieval

The system MUST return a single book with author and category relationships included.

#### Scenario: Retrieve existing book

- GIVEN a book with id=01H0A2B3C4D5E6F7G8H9J0K1M2 exists
- WHEN GET /api/v1/books/01H0A2B3C4D5E6F7G8H9J0K1M2 is requested
- THEN the book is returned with author and category nested in attributes

#### Scenario: Non-existent book returns 404

- GIVEN no book with id=NONEXISTENT exists
- WHEN GET /api/v1/books/NONEXISTENT is requested
- THEN HTTP 404 is returned

### Requirement: Authenticated Book Creation

The system MUST allow librarians and admins to create books with validated metadata, and MUST reject unauthenticated or user-role requests.

#### Scenario: Librarian creates book

- GIVEN an authenticated librarian with valid author_id, category_id, and unique ISBN
- WHEN POST /api/v1/books is submitted
- THEN HTTP 201 is returned with the created book in JSON:API format

#### Scenario: Reject duplicate ISBN

- GIVEN a book with ISBN "978-0-123456-78-9" exists
- WHEN POST /api/v1/books with the same ISBN is submitted
- THEN HTTP 422 is returned with error code VALIDATION_ERROR (caught by FormRequest unique rule before reaching the action)

#### Scenario: Reject missing required fields

- GIVEN an authenticated librarian submits POST /api/v1/books without title
- WHEN the request is validated
- THEN HTTP 422 is returned with validation errors

#### Scenario: Reject user role

- GIVEN an authenticated user with role=user
- WHEN POST /api/v1/books is attempted
- THEN HTTP 403 is returned

### Requirement: Authenticated Book Update

The system MUST allow librarians and admins to update books, validate uniqueness, and reject unauthorized requests.

#### Scenario: Librarian updates book

- GIVEN an authenticated librarian and a book exists
- WHEN PUT /api/v1/books/{id} with updated title is submitted
- THEN HTTP 200 is returned with the updated book

#### Scenario: ISBN conflict on update

- GIVEN book A with ISBN "111" and book B with ISBN "222"
- WHEN PUT /api/v1/books/{B} with isbn="111" is submitted
- THEN HTTP 422 DUPLICATE_ISBN is returned

### Requirement: Authenticated Book Deletion

The system MUST soft-delete books only when no active loans exist, and MUST throw BookHasActiveLoansException otherwise.

#### Scenario: Delete book with no active loans

- GIVEN a book exists and LoanChecker::hasActiveLoans() returns false
- WHEN DELETE /api/v1/books/{id} is submitted by a librarian
- THEN the book is soft-deleted and HTTP 204 is returned

#### Scenario: Prevent deletion with active loans

- GIVEN a book exists and LoanChecker::hasActiveLoans() returns true
- WHEN DELETE /api/v1/books/{id} is submitted
- THEN HTTP 409 BOOK_HAS_ACTIVE_LOANS is returned and the book is not deleted

### Requirement: JSON:API Response Format

The system MUST return book resources in JSON:API format with type="books", ULID id, and attributes including available_copies.

#### Scenario: Book resource shape

- GIVEN a book is retrieved or created
- WHEN the response is serialized via BookResource
- THEN the response includes data.type="books", data.id={ulid}, data.attributes.available_copies=0, and nested author/category objects

### Requirement: Factory and Testing Support

The system MUST provide a BookFactory that creates valid books with authors and categories for testing.

#### Scenario: Factory creates valid book

- GIVEN BookFactory::new()->create() is called
- WHEN the book is persisted
- THEN a Book with valid author_id, category_id, and all nullable fields populated is created
