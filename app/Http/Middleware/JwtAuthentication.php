<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for JWT authentication.
 * Stub implementation - customize for your auth requirements.
 */
final class JwtAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        // Implement JWT validation here
        return $next($request);
    }
}
