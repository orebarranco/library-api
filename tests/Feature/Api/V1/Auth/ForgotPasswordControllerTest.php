<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->endpoint = '/api/v1/auth/forgot-password';
    Notification::fake();
});
it('sends a password reset notification to a registered user', function (): void {
    $user = User::factory()->create();

    $this->postJson($this->endpoint, ['email' => $user->email])
        ->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});
it('builds reset url using frontend_url config', function (): void {
    config(['app.frontend_url' => 'https://myapp.com']);
    $user = User::factory()->create();

    $this->postJson($this->endpoint, ['email' => $user->email])
        ->assertOk();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $url = $notification->toMail($user)->actionUrl;
        expect($url)->toStartWith('https://myapp.com/reset-password?token=');

        return true;
    });
});
it('returns 200 even for an unregistered email to prevent enumeration', function (): void {
    $this->postJson($this->endpoint, ['email' => 'nobody@example.com'])
        ->assertOk();

    Notification::assertNothingSent();
});
it('fails with validation errors', function (array $payload, string $field): void {
    $this->postJson($this->endpoint, $payload)
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR')
        ->assertJsonFragment(['source' => ['pointer' => '/data/attributes/'.$field]]);
})->with([
    'missing email' => [['email' => ''], 'email'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
]);
