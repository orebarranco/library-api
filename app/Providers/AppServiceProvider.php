<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fine\GenerateFineAction;
use App\Contracts\EloquentFineChecker;
use App\Contracts\EloquentLoanChecker;
use App\Contracts\FineChecker;
use App\Contracts\FineGenerator;
use App\Contracts\LoanChecker;
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
        // Loans exist from Module 8 onwards, so the null placeholder is no longer needed.
        $this->app->bind(LoanChecker::class, EloquentLoanChecker::class);

        // Fines are persisted from Module 9 onwards, replacing the null placeholders.
        $this->app->bind(FineGenerator::class, GenerateFineAction::class);
        $this->app->bind(FineChecker::class, EloquentFineChecker::class);
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

        // Stacked on top of `api` on the few endpoints that move stock or money,
        // where a burst is abuse rather than an impatient client. Every route
        // carrying it sits behind auth:sanctum, so the user key is the one that
        // gets used; `??` short-circuits the whole chain if that ever changes.
        RateLimiter::for('critical', fn (Request $request): Limit => Limit::perMinute(10)->by($request->user()->id ?? (string) $request->ip()));
    }
}
