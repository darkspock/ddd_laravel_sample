<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Logging;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Simple logging helper that uses Laravel's Log facade.
 */
final class LoggerHelper
{
    public static function logException(Throwable $exception): void
    {
        Log::error($exception->getMessage(), [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
