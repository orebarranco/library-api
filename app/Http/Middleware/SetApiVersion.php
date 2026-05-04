<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetApiVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next, string $apiVersion = 'v1'): Response
    {
        $request->attributes->set('api_version', $apiVersion);

        $response = $next($request);

        $response->headers->set('X-API-Version', $apiVersion);

        return $response;
    }
}
