<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/auth/email/resend';
    Notification::fake();
});
it('sends a verification email to an unverified user', function (): void {
    $user = User::factory()->unverified()->create();
    Sanctum::actingAs($user);

    $this->postJson($this->endpoint)
        ->assertNoContent();

    Notification::assertSentTo($user, VerifyEmail::class);
});
it('does not resend if email is already verified', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson($this->endpoint)
        ->assertNoContent();

    Notification::assertNotSentTo($user, VerifyEmail::class);
});
it('requires authentication', function (): void {
    $this->postJson($this->endpoint)
        ->assertUnauthorized()
        ->assertJsonPath('errors.0.code', 'UNAUTHENTICATED');
});
