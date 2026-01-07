<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for partner API authentication.
 * Stub implementation - customize for your auth requirements.
 */
final class PartnerApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Implement partner API authentication here
        return $next($request);
    }
}
