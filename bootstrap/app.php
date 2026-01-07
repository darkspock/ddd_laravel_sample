<?php

declare(strict_types=1);

use App\Http\Middleware\BearerTokenMiddleware;
use App\Http\Middleware\JwtAuthentication;
use App\Http\Middleware\MachineTokenMiddleware;
use App\Http\Middleware\PartnerApiAuth;
use App\Http\Middleware\QueryLogMiddleware;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__ . '/../routes/web.php',
        api:      __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(QueryLogMiddleware::class);

        // Enable CORS for API routes
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->alias([
            'api.auth' => BearerTokenMiddleware::class,
            'jwt.auth' => JwtAuthentication::class,
            'machine.token' => MachineTokenMiddleware::class,
            'partner.api.auth' => PartnerApiAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Report exceptions to Bugsnag (especially important for queue workers)
        $exceptions->report(function (Throwable $e) {
            if (app()->bound('bugsnag')) {
                Bugsnag::notifyException($e);
            }
        });
    })->create();
