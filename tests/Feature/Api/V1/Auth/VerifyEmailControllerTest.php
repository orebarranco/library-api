<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    $this->user = User::factory()->unverified()->create();
});
function verificationUrl(User $user): string
{
    return URL::temporarySignedRoute(
        'api.v1.auth.verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->email)],
    );
}

it('verifies email with a valid signed url', function (): void {
    $this->getJson(verificationUrl($this->user))
        ->assertOk()
        ->assertJsonPath('data.type', 'users')
        ->assertJsonPath('data.attributes.email', $this->user->email);

    expect($this->user->fresh()->hasVerifiedEmail())->toBeTrue();
});
it('is idempotent for already verified users', function (): void {
    $this->user->markEmailAsVerified();

    $this->getJson(verificationUrl($this->user))
        ->assertOk();
});
it('fails with an invalid signature', function (): void {
    $url = route('api.v1.auth.verification.verify', [
        'id' => $this->user->getKey(),
        'hash' => sha1((string) $this->user->email),
    ]);

    $this->getJson($url)
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INVALID_SIGNATURE');
});
it('fails when hash does not match user email', function (): void {
    $url = URL::temporarySignedRoute(
        'api.v1.auth.verification.verify',
        now()->addMinutes(60),
        ['id' => $this->user->getKey(), 'hash' => sha1('wrong@email.com')],
    );

    $this->getJson($url)
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'INVALID_SIGNATURE');
});
it('returns 404 for a non-existent user', function (): void {
    $url = URL::temporarySignedRoute(
        'api.v1.auth.verification.verify',
        now()->addMinutes(60),
        ['id' => 'non-existent-id', 'hash' => 'any-hash'],
    );

    $this->getJson($url)
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', 'NOT_FOUND');
});
