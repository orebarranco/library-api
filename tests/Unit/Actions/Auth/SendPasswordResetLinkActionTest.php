<?php

declare(strict_types=1);
use App\Actions\Auth\SendPasswordResetLinkAction;
use App\DTOs\Auth\ForgotPasswordDTO;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->action = new SendPasswordResetLinkAction();
    Notification::fake();
});
it('sends a password reset notification to an existing user', function (): void {
    $user = User::factory()->create();

    $this->action->execute(new ForgotPasswordDTO(email: $user->email));

    Notification::assertSentTo($user, ResetPassword::class);
});
it('does not fail for a non-existent email', function (): void {
    $this->action->execute(new ForgotPasswordDTO(email: 'nobody@example.com'));

    Notification::assertNothingSent();
});
