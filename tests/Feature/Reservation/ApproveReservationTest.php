<?php

declare(strict_types=1);

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationApprovedNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->librarian = User::factory()->create(['role' => UserRole::Librarian]);
    $this->user = User::factory()->create(['role' => UserRole::User]);
    $this->reservation = Reservation::factory()->pending()->create();
});

it('librarian can approve a pending reservation', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson("/api/v1/reservations/{$this->reservation->id}/approve")
        ->assertSuccessful();
});

it('status changes to approved with approved_at expires_at and approved_by set', function (): void {
    Sanctum::actingAs($this->librarian);

    $response = $this->postJson("/api/v1/reservations/{$this->reservation->id}/approve")
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', ReservationStatus::Approved->value);

    expect($response->json('data.attributes.approved_at'))->not->toBeNull();
    expect($response->json('data.attributes.expires_at'))->not->toBeNull();

    $this->assertDatabaseHas('reservations', [
        'id' => $this->reservation->id,
        'status' => ReservationStatus::Approved->value,
        'approved_by' => $this->librarian->id,
    ]);
});

it('user receives ReservationApprovedNotification on approval', function (): void {
    Notification::fake();
    Sanctum::actingAs($this->librarian);

    $this->postJson("/api/v1/reservations/{$this->reservation->id}/approve")
        ->assertSuccessful();

    Notification::assertSentTo(
        $this->reservation->loadMissing('user')->user,
        ReservationApprovedNotification::class,
    );
});

it('does not notify when approval is rejected as not pending', function (): void {
    Notification::fake();
    Sanctum::actingAs($this->librarian);

    $rejected = Reservation::factory()->rejected()->create();

    $this->postJson("/api/v1/reservations/{$rejected->id}/approve")
        ->assertUnprocessable();

    Notification::assertNothingSent();
});

it('returns 422 RESERVATION_NOT_PENDING if reservation is not pending', function (): void {
    Sanctum::actingAs($this->librarian);

    $rejected = Reservation::factory()->rejected()->create();

    $this->postJson("/api/v1/reservations/{$rejected->id}/approve")
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'RESERVATION_NOT_PENDING');
});

it('returns 403 for user role', function (): void {
    Sanctum::actingAs($this->user);

    $this->postJson("/api/v1/reservations/{$this->reservation->id}/approve")
        ->assertForbidden();
});

it('returns 404 for non-existent reservation', function (): void {
    Sanctum::actingAs($this->librarian);

    $this->postJson('/api/v1/reservations/non-existent-id/approve')
        ->assertNotFound();
});
