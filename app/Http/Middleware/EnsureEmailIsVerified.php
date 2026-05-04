<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Auth\EmailNotVerifiedException;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureEmailIsVerified
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user() instanceof MustVerifyEmail &&
            ! $request->user()->hasVerifiedEmail()
        ) {
            throw new EmailNotVerifiedException;
        }

        return $next($request);
    }
}
