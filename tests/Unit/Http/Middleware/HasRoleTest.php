<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Exceptions\Auth\InsufficientPermissionsException;
use App\Http\Middleware\HasRole;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

test('passes request when user has required role', function (): void {
    $user = User::factory()->make([
        'role' => UserRole::Librarian,
    ]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $user);

    $middleware = new HasRole();

    $response = $middleware->handle(
        $request,
        fn () => response()->json(['ok' => true]),
        UserRole::Librarian->value,
    );

    expect($response)
        ->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())
        ->toBe(200);
});

test('passes request when user has one of multiple accepted roles', function (): void {
    $user = User::factory()->make([
        'role' => UserRole::Admin,
    ]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $user);

    $middleware = new HasRole();

    $response = $middleware->handle(
        $request,
        fn () => response()->json(['ok' => true]),
        UserRole::Librarian->value,
        UserRole::Admin->value,
    );

    expect($response->getStatusCode())->toBe(200);
});

test('throws exception when user role is insufficient', function (): void {
    $user = User::factory()->make([
        'role' => UserRole::User,
    ]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $user);

    $middleware = new HasRole();

    expect(fn (): Response => $middleware->handle(
        $request,
        fn () => response()->json(),
        UserRole::Admin->value,
    ))->toThrow(InsufficientPermissionsException::class);
});

test('throws exception when user is not authenticated', function (): void {
    $request = Request::create('/test');

    $middleware = new HasRole();

    expect(fn (): Response => $middleware->handle(
        $request,
        fn () => response()->json(),
        UserRole::Admin->value,
    ))->toThrow(InsufficientPermissionsException::class);
});
