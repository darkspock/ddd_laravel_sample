<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Bus\CommandBus;

use Src\Shared\Framework\Application\Commands\CommandInterface;

interface CommandBusInterface
{
    public function dispatch(CommandInterface $command, bool $async = false): void;

    public function dispatchProcess(CommandInterface $process, bool $async = false): void;

    public function dispatchAsync(CommandInterface $command): void;

    public function dispatchIgnoreException(CommandInterface $command, string $exceptionClass): void;

    /**
     * @param  array<string>  $exceptionClasses
     */
    public function dispatchIgnoreExceptions(CommandInterface $command, array $exceptionClasses): void;
}
