<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\Services;

interface LoggerInterface
{
    public function error(string $message): void;

    public function info(string $message): void;

    public function warning(string $message): void;

    public function success(string $message): void;
}
