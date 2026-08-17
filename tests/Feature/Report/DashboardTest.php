<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/reports/dashboard';
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->member = User::factory()->create(['role' => UserRole::User]);
});

it('librarian can access the dashboard', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.type', 'dashboards')
        ->assertJsonPath('data.id', 'current');
});

it('admin can access the dashboard', function (): void {
    Sanctum::actingAs($this->admin);

    $this->getJson($this->endpoint)->assertOk();
});

it('response includes the four activity counters', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'attributes' => [
                    'active_loans_count',
                    'overdue_loans_count',
                    'pending_reservations_count',
                    'total_pending_fines_amount',
                ],
            ],
        ]);
});

it('counts are accurate based on database state', function (): void {
    // Loans are hung off already-completed reservations so that the reservation
    // each loan factory would otherwise create does not count as pending.
    Loan::factory()->active()->count(3)->for(Reservation::factory()->completed())->create();
    Loan::factory()->overdue()->count(2)->for(Reservation::factory()->completed())->create();
    Loan::factory()->returned()->count(4)->for(Reservation::factory()->completed())->create();

    Reservation::factory()->pending()->count(2)->create();
    Reservation::factory()->approved()->create();

    // Detached from any loan for the same reason.
    Fine::factory()->pending()->create(['loan_id' => null, 'amount' => 30.0]);
    Fine::factory()->partiallyPaid()->create(['loan_id' => null, 'amount' => 20.0]);
    Fine::factory()->paid()->create(['loan_id' => null, 'amount' => 90.0]);
    Fine::factory()->waived()->create(['loan_id' => null, 'amount' => 75.0]);

    Sanctum::actingAs($this->librarian);

    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.attributes.active_loans_count', 5)
        ->assertJsonPath('data.attributes.overdue_loans_count', 2)
        ->assertJsonPath('data.attributes.pending_reservations_count', 2)
        ->assertJsonPath('data.attributes.total_pending_fines_amount', 40);
});

it('reports zeroes for an empty library', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.attributes.active_loans_count', 0)
        ->assertJsonPath('data.attributes.overdue_loans_count', 0)
        ->assertJsonPath('data.attributes.pending_reservations_count', 0)
        ->assertJsonPath('data.attributes.total_pending_fines_amount', 0);
});

it('returns 403 for user role', function (): void {
    Sanctum::actingAs($this->member);

    $this->getJson($this->endpoint)->assertForbidden();
});

it('returns 401 for unauthenticated request', function (): void {
    $this->getJson($this->endpoint)->assertUnauthorized();
});
