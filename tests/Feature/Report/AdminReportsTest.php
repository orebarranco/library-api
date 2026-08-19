<?php

declare(strict_types=1);

use App\Enums\BookCopyStatus;
use App\Enums\FineStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonInterface;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
});

/**
 * Every endpoint the admin owns exclusively.
 */
dataset('admin reports', [
    'user activity' => '/api/v1/reports/user-activity',
    'inventory status' => '/api/v1/reports/inventory-status',
    'fines revenue' => '/api/v1/reports/fines-revenue',
    'category trends' => '/api/v1/reports/trends/category',
    'month trends' => '/api/v1/reports/trends/month',
]);

/**
 * Loans of a book in the given category, each through an already-completed
 * reservation so the fixture never leaks into the pending-reservations report.
 */
function loansOnCategory(Category $category, int $count, CarbonInterface $loanedAt): void
{
    $book = Book::factory()->for($category)->create();

    Loan::factory()
        ->count($count)
        ->for(BookCopy::factory()->for($book))
        ->for(Reservation::factory()->completed()->for($book))
        ->create(['loaned_at' => $loanedAt]);
}

it('admin can access all advanced reports', function (string $url): void {
    Sanctum::actingAs($this->admin);

    $this->getJson($url)->assertOk()->assertJsonStructure(['data', 'meta']);
})->with('admin reports');

it('all admin reports return 403 for librarian and user roles', function (string $url): void {
    foreach ([$this->librarian, $this->member] as $forbidden) {
        Sanctum::actingAs($forbidden);

        $this->getJson($url)->assertForbidden();
    }
})->with('admin reports');

it('user activity counts loans, reservations and fines per user', function (): void {
    $borrower = User::factory()->create(['name' => 'Avid Reader']);
    Loan::factory()->count(2)->for($borrower)->for(Reservation::factory()->completed())->create();
    Reservation::factory()->count(3)->for($borrower)->create();
    Fine::factory()->for($borrower)->create();

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/v1/reports/user-activity')->assertOk();

    $entry = collect($response->json('data'))->firstWhere('id', $borrower->id);

    expect($entry['attributes']['loans_count'])->toBe(2)
        // The reservations backing those loans belong to their own borrowers.
        ->and($entry['attributes']['reservations_count'])->toBe(3)
        ->and($entry['attributes']['fines_count'])->toBe(1)
        ->and($response->json('meta.pagination.per_page'))->toBe(15);
});

it('user activity period filter limits to the specified window', function (): void {
    $borrower = User::factory()->create();
    Loan::factory()->for($borrower)->for(Reservation::factory()->completed())->create([
        'loaned_at' => now()->subDays(2),
    ]);
    Loan::factory()->for($borrower)->for(Reservation::factory()->completed())->create([
        'loaned_at' => now()->subDays(60),
    ]);

    Sanctum::actingAs($this->admin);

    $recent = $this->getJson('/api/v1/reports/user-activity?period=7days')->assertOk();
    $everything = $this->getJson('/api/v1/reports/user-activity')->assertOk();

    expect(collect($recent->json('data'))->firstWhere('id', $borrower->id)['attributes']['loans_count'])->toBe(1)
        ->and(collect($everything->json('data'))->firstWhere('id', $borrower->id)['attributes']['loans_count'])->toBe(2);
});

it('user activity rejects an unknown period', function (): void {
    Sanctum::actingAs($this->admin);

    $this->getJson('/api/v1/reports/user-activity?period=forever')->assertUnprocessable();
});

it('inventory status includes copy counts per status per book', function (): void {
    $book = Book::factory()->create(['title' => 'Stocked Title']);
    BookCopy::factory()->count(3)->for($book)->create(['status' => BookCopyStatus::Available]);
    BookCopy::factory()->count(2)->for($book)->create(['status' => BookCopyStatus::Loaned]);
    BookCopy::factory()->for($book)->create(['status' => BookCopyStatus::Maintenance]);
    BookCopy::factory()->for($book)->create(['status' => BookCopyStatus::Lost]);

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/v1/reports/inventory-status')->assertOk();

    $entry = collect($response->json('data'))->firstWhere('id', $book->id);

    expect($entry['attributes'])->toMatchArray([
        'title' => 'Stocked Title',
        'total_copies' => 7,
        'available_copies' => 3,
        'loaned_copies' => 2,
        'maintenance_copies' => 1,
        'lost_copies' => 1,
    ]);
});

it('inventory status lists books without copies at zero', function (): void {
    $book = Book::factory()->create(['title' => 'Never Acquired']);

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/v1/reports/inventory-status')->assertOk();

    $entry = collect($response->json('data'))->firstWhere('id', $book->id);

    expect($entry['attributes']['total_copies'])->toBe(0)
        ->and($entry['attributes']['available_copies'])->toBe(0);
});

it('fines revenue supports period filter', function (): void {
    Fine::factory()->create(['amount' => 40.0, 'created_at' => now()->subDays(2)]);
    Fine::factory()->create(['amount' => 100.0, 'created_at' => now()->subDays(60)]);

    Sanctum::actingAs($this->admin);

    $recent = $this->getJson('/api/v1/reports/fines-revenue?period=7days')->assertOk();
    $everything = $this->getJson('/api/v1/reports/fines-revenue')->assertOk();

    expect($recent->json('data.attributes.period'))->toBe('7days')
        ->and($recent->json('data.attributes.total_generated'))->toBe(40)
        ->and($recent->json('data.attributes.fines_generated_count'))->toBe(1)
        ->and($everything->json('data.attributes.period'))->toBeNull()
        ->and($everything->json('data.attributes.total_generated'))->toBe(140);
});

it('fines revenue separates collected, waived and outstanding money', function (): void {
    Fine::factory()->create([
        'amount' => 50.0,
        'amount_paid' => 50.0,
        'status' => FineStatus::Paid,
    ]);
    Fine::factory()->create([
        'amount' => 30.0,
        'amount_paid' => 10.0,
        'status' => FineStatus::PartiallyPaid,
    ]);
    Fine::factory()->create([
        'amount' => 20.0,
        'status' => FineStatus::Waived,
        'waived_by' => $this->admin->id,
        'waived_reason' => 'Goodwill',
    ]);

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/v1/reports/fines-revenue')->assertOk();

    expect($response->json('data.attributes.total_generated'))->toBe(100)
        ->and($response->json('data.attributes.total_collected'))->toBe(60)
        ->and($response->json('data.attributes.total_waived'))->toBe(20)
        // Only the 20 still owed on the partially paid fine counts as debt.
        ->and($response->json('data.attributes.total_outstanding'))->toBe(20);
});

it('trends supports type: category and month', function (): void {
    $fiction = Category::factory()->create(['name' => 'Fiction']);
    $essays = Category::factory()->create(['name' => 'Essays']);

    loansOnCategory($fiction, 3, now()->subMonth());
    loansOnCategory($essays, 1, now());

    Sanctum::actingAs($this->admin);

    $byCategory = $this->getJson('/api/v1/reports/trends/category')->assertOk();
    $byMonth = $this->getJson('/api/v1/reports/trends/month')->assertOk();

    expect($byCategory->json('data.id'))->toBe('category')
        ->and($byCategory->json('data.attributes.period'))->toBe('1year')
        ->and($byCategory->json('data.attributes.series'))->toBe([
            ['label' => 'Fiction', 'total' => 3],
            ['label' => 'Essays', 'total' => 1],
        ])
        ->and($byMonth->json('data.attributes.series'))->toBe([
            ['label' => now()->subMonth()->format('Y-m'), 'total' => 3],
            ['label' => now()->format('Y-m'), 'total' => 1],
        ]);
});

it('trends honours an explicit period', function (): void {
    $fiction = Category::factory()->create(['name' => 'Fiction']);
    loansOnCategory($fiction, 2, now()->subDays(60));

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/v1/reports/trends/category?period=7days')->assertOk();

    expect($response->json('data.attributes.period'))->toBe('7days')
        ->and($response->json('data.attributes.series'))->toBe([]);
});

it('trends rejects an unknown type', function (): void {
    Sanctum::actingAs($this->admin);

    $this->getJson('/api/v1/reports/trends/decade')->assertNotFound();
});
