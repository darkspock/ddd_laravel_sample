<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for machine-to-machine token authentication.
 * Stub implementation - customize for your auth requirements.
 */
final class MachineTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Implement machine token validation here
        return $next($request);
    }
}
