<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonInterface;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
});

/**
 * Loans on a copy of the given book, each through an already-completed
 * reservation of that same book: a pending one would show up in the
 * pending-reservations report and a reservation of its own auto-created book
 * would add an unwanted title to the popular-books ranking.
 */
function loansOnBook(Book $book, int $count, ?CarbonInterface $loanedAt = null): void
{
    $attributes = $loanedAt instanceof CarbonInterface ? ['loaned_at' => $loanedAt] : [];

    Loan::factory()
        ->count($count)
        ->for(BookCopy::factory()->for($book))
        ->for(Reservation::factory()->completed()->for($book))
        ->create($attributes);
}

it('active loans report returns paginated active loans', function (): void {
    Loan::factory()->active()->count(3)->for(Reservation::factory()->completed())->create();
    Loan::factory()->overdue()->count(2)->for(Reservation::factory()->completed())->create();
    Loan::factory()->returned()->count(4)->for(Reservation::factory()->completed())->create();

    Sanctum::actingAs($this->librarian);

    $response = $this->getJson('/api/v1/reports/active-loans')->assertOk();

    expect($response->json('data'))->toHaveCount(5)
        ->and($response->json('meta.pagination.total'))->toBe(5)
        ->and($response->json('meta.pagination.per_page'))->toBe(15);
});

it('overdue loans report returns paginated list with days_overdue per entry', function (): void {
    Loan::factory()->overdue()->for(Reservation::factory()->completed())->create([
        'due_date' => now()->subDays(7),
    ]);
    Loan::factory()->active()->count(2)->for(Reservation::factory()->completed())->create();

    Sanctum::actingAs($this->librarian);

    $response = $this->getJson('/api/v1/reports/overdue-loans')->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.days_overdue'))->toBe(7);
});

it('pending reservations report returns paginated list with only pending status', function (): void {
    Reservation::factory()->pending()->count(3)->create();
    Reservation::factory()->approved()->create();
    Reservation::factory()->rejected()->create();
    Reservation::factory()->expired()->create();

    Sanctum::actingAs($this->librarian);

    $response = $this->getJson('/api/v1/reports/pending-reservations')->assertOk();

    expect($response->json('data'))->toHaveCount(3)
        ->and(collect($response->json('data'))->pluck('attributes.status')->unique()->all())
        ->toBe(['pending']);
});

it('popular books report returns paginated list ordered by total loan count', function (): void {
    $popular = Book::factory()->create(['title' => 'Borrowed Often']);
    $quiet = Book::factory()->create(['title' => 'Borrowed Once']);
    Book::factory()->create(['title' => 'Never Borrowed']);

    loansOnBook($popular, 3);
    loansOnBook($quiet, 1);

    Sanctum::actingAs($this->librarian);

    $response = $this->getJson('/api/v1/reports/popular-books')->assertOk();

    expect(collect($response->json('data'))->pluck('attributes.title')->all())
        ->toBe(['Borrowed Often', 'Borrowed Once', 'Never Borrowed'])
        ->and($response->json('data.0.attributes.total_loans'))->toBe(3)
        ->and($response->json('data.1.attributes.total_loans'))->toBe(1)
        ->and($response->json('data.2.attributes.total_loans'))->toBe(0);
});

it('popular books period filter limits to specified window', function (): void {
    $old = Book::factory()->create(['title' => 'Popular Last Year']);
    $recent = Book::factory()->create(['title' => 'Popular This Month']);

    loansOnBook($old, 5, now()->subDays(200));
    loansOnBook($recent, 1, now()->subDays(5));

    Sanctum::actingAs($this->librarian);

    $allTime = $this->getJson('/api/v1/reports/popular-books')->assertOk();

    expect($allTime->json('data.0.attributes.title'))->toBe('Popular Last Year')
        ->and($allTime->json('data.0.attributes.total_loans'))->toBe(5);

    $windowed = $this->getJson('/api/v1/reports/popular-books?period=30days')->assertOk();

    expect($windowed->json('data.0.attributes.title'))->toBe('Popular This Month')
        ->and($windowed->json('data.0.attributes.total_loans'))->toBe(1)
        ->and($windowed->json('data.1.attributes.total_loans'))->toBe(0);
});

it('rejects an unsupported period', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson('/api/v1/reports/popular-books?period=2weeks')
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
});

it('active loans report supports the page parameter', function (): void {
    Loan::factory()->active()->count(18)->for(Reservation::factory()->completed())->create();

    Sanctum::actingAs($this->librarian);

    expect($this->getJson('/api/v1/reports/active-loans')->assertOk()->json('data'))->toHaveCount(15)
        ->and($this->getJson('/api/v1/reports/active-loans?page=2')->assertOk()->json('data'))->toHaveCount(3);
});

it('overdue loans report supports the page parameter', function (): void {
    Loan::factory()->overdue()->count(18)->for(Reservation::factory()->completed())->create();

    Sanctum::actingAs($this->librarian);

    expect($this->getJson('/api/v1/reports/overdue-loans')->assertOk()->json('data'))->toHaveCount(15)
        ->and($this->getJson('/api/v1/reports/overdue-loans?page=2')->assertOk()->json('data'))->toHaveCount(3);
});

it('pending reservations report supports the page parameter', function (): void {
    Reservation::factory()->pending()->count(18)->create();

    Sanctum::actingAs($this->librarian);

    expect($this->getJson('/api/v1/reports/pending-reservations')->assertOk()->json('data'))->toHaveCount(15)
        ->and($this->getJson('/api/v1/reports/pending-reservations?page=2')->assertOk()->json('data'))->toHaveCount(3);
});

it('popular books report supports the page parameter', function (): void {
    Book::factory()->count(18)->create();

    Sanctum::actingAs($this->librarian);

    expect($this->getJson('/api/v1/reports/popular-books')->assertOk()->json('data'))->toHaveCount(15)
        ->and($this->getJson('/api/v1/reports/popular-books?page=2')->assertOk()->json('data'))->toHaveCount(3);
});

it('keeps the period filter on pagination links', function (): void {
    Book::factory()->count(18)->create();

    Sanctum::actingAs($this->librarian);

    $response = $this->getJson('/api/v1/reports/popular-books?period=30days')->assertOk();

    expect($response->json('links.next'))->toContain('period=30days');
});

it('all endpoints return 403 for user role', function (string $endpoint): void {
    Sanctum::actingAs($this->member);

    $this->getJson($endpoint)->assertForbidden();
})->with([
    '/api/v1/reports/dashboard',
    '/api/v1/reports/active-loans',
    '/api/v1/reports/overdue-loans',
    '/api/v1/reports/pending-reservations',
    '/api/v1/reports/popular-books',
]);

it('all endpoints return 401 for unauthenticated requests', function (string $endpoint): void {
    $this->getJson($endpoint)->assertUnauthorized();
})->with([
    '/api/v1/reports/dashboard',
    '/api/v1/reports/active-loans',
    '/api/v1/reports/overdue-loans',
    '/api/v1/reports/pending-reservations',
    '/api/v1/reports/popular-books',
]);
