<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Helpers;

final class FileHelper
{
    public static function fixPath(string $host, string $path): string
    {

        do {
            if ($host[-1] === '/') {
                $host = substr($host, 0, -1);
            }
            if ($path[0] === '/') {

                $path = substr($path, 1);
            }
        } while ($host[-1] === '/' || $path[0] === '/');

        return $host . '/' . $path;
    }
}
