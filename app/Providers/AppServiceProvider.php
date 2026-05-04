<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict();

        AnonymousResourceCollection::macro('paginationInformation', fn (Request $request, array $paginated, array $default): array => ['links' => $default['links']]);

        $this->configureRateLimiting();
        $this->configureEmailVerification();
        $this->configurePasswordReset();
    }

    private function configurePasswordReset(): void
    {
        ResetPassword::createUrlUsing(function (mixed $user, string $token): string {
            /** @var User $user */
            $baseUrl = mb_rtrim(Config::string('app.frontend_url', Config::string('app.url')), '/');

            return $baseUrl.'/reset-password?token='.urlencode($token).'&email='.urlencode($user->getEmailForPasswordReset());
        });
    }

    private function configureEmailVerification(): void
    {
        VerifyEmail::createUrlUsing(fn (User $notifiable): string => URL::temporarySignedRoute(
            'api.v1.auth.verification.verify',
            now()->addMinutes(Config::integer('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        ));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('api', fn (Request $request): Limit => $request->user()
            ? Limit::perMinute(120)->by($request->user()->id)
            : Limit::perMinute(60)->by($request->ip()));
    }
}
