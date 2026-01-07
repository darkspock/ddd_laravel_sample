<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

use function array_key_exists;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CacheHelper
{
    /** @var array<string,mixed> */
    private static array $cache = [];

    public static function onceByKey(string $key, callable $callback, int $ttl = 0): mixed
    {
        $versionedKey = self::addVersionPrefix($key);

        if ($ttl === 0) {
            if (array_key_exists($versionedKey, self::$cache)) {
                return self::$cache[$versionedKey];
            }

            $value = $callback();
            self::$cache[$versionedKey] = $value;

            return $value;
        }

        try {
            return Cache::remember($versionedKey, $ttl, static function () use ($callback) {
                return $callback();
            });
        } catch (Throwable $e) {
            // Handle deserialization errors during rolling deployments
            if (self::isSerializationError($e)) {
                Log::warning('Cache deserialization error, clearing key', [
                    'key' => $versionedKey,
                    'error' => $e->getMessage(),
                ]);

                Cache::forget($versionedKey);

                return Cache::remember($versionedKey, $ttl, static function () use ($callback) {
                    return $callback();
                });
            }

            throw $e;
        }
    }

    private static function addVersionPrefix(string $key): string
    {
        $gitCommit = config('app.git_commit', 'dev');
        // Use short hash (first 8 chars) to keep keys readable
        $version = is_string($gitCommit) ? substr($gitCommit, 0, 8) : 'dev';

        return "v:{$version}:{$key}";
    }

    private static function isSerializationError(Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'unserialize') ||
               str_contains($message, 'Error at offset') ||
               str_contains($message, '__PHP_Incomplete_Class') ||
               (str_contains($message, 'Class') && str_contains($message, 'not found'));
    }

    public static function reset(): void
    {
        self::$cache = [];
    }

}
