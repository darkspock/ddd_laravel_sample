<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\IO;

use Src\Shared\Framework\Domain\Services\LoggerInterface;

class EchoLogger implements LoggerInterface
{
    public function error(string $message): void
    {
        $this->output('ERROR', $message);
    }

    public function info(string $message): void
    {
        $this->output('INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->output('WARNING', $message);
    }

    public function success(string $message): void
    {
        $this->output('OK', $message);
    }

    private function output(string $type, string $message): void
    {
        echo ' [' . $type . '] ' . $message . "\n";
    }
}
