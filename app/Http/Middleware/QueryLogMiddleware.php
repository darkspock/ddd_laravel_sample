<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for logging database queries (development/debugging).
 * In production, this is typically disabled.
 */
final class QueryLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Query logging is disabled by default
        // Enable in development by uncommenting DB::enableQueryLog()
        return $next($request);
    }
}
