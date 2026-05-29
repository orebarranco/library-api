<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});
describe('auth rate limiter', function (): void {
    it('blocks login after 5 attempts per minute', function (): void {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $this->user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ])->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    });

    it('blocks register after 5 attempts per minute', function (): void {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/register', [
                'name' => 'Test User',
                'email' => fake()->unique()->safeEmail(),
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);
        }

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    });

    it('returns 429 error in json api format', function (): void {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $this->user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ])
            ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS)
            ->assertJsonPath('errors.0.status', '429')
            ->assertJsonPath('errors.0.code', 'TOO_MANY_REQUESTS');
    });
});
describe('api rate limiter', function (): void {
    it('blocks authenticated requests after the per-minute limit', function (): void {
        RateLimiter::for('api', fn (): Limit => Limit::perMinute(3)->by($this->user->id));

        Sanctum::actingAs($this->user);

        foreach (range(1, 3) as $attempt) {
            $this->getJson('/api/v1/auth/me')->assertOk();
        }

        $this->getJson('/api/v1/auth/me')
            ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    });

    it('applies separate limits per user', function (): void {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(2)->by($request->user()?->id ?? $request->ip()));

        $otherUser = User::factory()->create();

        Sanctum::actingAs($this->user);
        $this->getJson('/api/v1/auth/me')->assertOk();
        $this->getJson('/api/v1/auth/me')->assertOk();
        $this->getJson('/api/v1/auth/me')->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);

        Sanctum::actingAs($otherUser);
        $this->getJson('/api/v1/auth/me')->assertOk();
    });
});
