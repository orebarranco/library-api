<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Exceptions\Auth\InsufficientPermissionsException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HasRole
{
    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws InsufficientPermissionsException
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new InsufficientPermissionsException();
        }

        $allowedRoles = array_map(
            UserRole::from(...),
            $roles,
        );

        if (in_array($user->role, $allowedRoles, true)) {
            return $next($request);
        }

        throw new InsufficientPermissionsException();
    }
}
