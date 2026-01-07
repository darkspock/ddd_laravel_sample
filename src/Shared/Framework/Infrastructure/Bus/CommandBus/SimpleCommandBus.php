<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Bus\CommandBus;

use Closure;
use Src\Shared\Framework\Application\Commands\CommandInterface;
use Src\Shared\Framework\Application\Commands\CommandJobInterface;
use Src\Shared\Framework\Helpers\Container;

use function get_class;

use RuntimeException;
use Throwable;

final class SimpleCommandBus implements CommandBusInterface
{
    /** @var array<string|Closure> */
    public static array $routes = [];

    public function dispatch(CommandInterface $command, bool $async = false): void
    {
        $commandClass = get_class($command);
        /** @var class-string<CommandHandlerInterface>|null|Closure $commandHandlerName */
        $commandHandlerName = self::$routes[$commandClass] ?? preg_replace('/Command$/', 'Handler', $commandClass);
        if ($commandHandlerName === null) {
            throw new RuntimeException('Handler not found for ' . $commandClass);
        }
        if ($commandHandlerName instanceof Closure) {
            $commandHandlerName($command);
            return;
        }
        if ($command instanceof CommandJobInterface && $command->shouldQueue()) {
            $async = true;
        }
        if (false === $async) {
            /** @var CommandHandlerInterface $commandHandler */
            $commandHandler = Container::getObjectInstance($commandHandlerName);
            $commandHandler->__invoke($command);
            return;
        }

        if ($command instanceof CommandJobInterface) {
            QueueHandler::dispatch($commandHandlerName, $command)->onQueue($command->getQueue()->value)->delay($command->getDelay());
            return;
        }
        QueueHandler::dispatch($commandHandlerName, $command);

    }

    public function dispatchProcess(CommandInterface $process, bool $async = false): void
    {
        $commandClass = get_class($process);
        /** @var class-string<CommandHandlerInterface>|null|Closure $processHandlerName */
        $processHandlerName = self::$routes[$commandClass] ?? preg_replace('/Process$/', 'ProcessHandler', $commandClass);
        if ($processHandlerName === null) {
            throw new RuntimeException('Handler not found for ' . $commandClass);
        }
        if ($processHandlerName instanceof Closure) {
            $processHandlerName($process);
            return;
        }
        if ($process instanceof CommandJobInterface && $process->shouldQueue()) {
            $async = true;
        }
        if (false === $async) {
            /** @var CommandHandlerInterface $processHandler */
            $processHandler = Container::getObjectInstance($processHandlerName);
            $processHandler->__invoke($process);
            return;
        }

        if ($process instanceof CommandJobInterface) {
            QueueHandler::dispatch($processHandlerName, $process)->onQueue($process->getQueue()->value)->delay($process->getDelay());
            return;
        }
        QueueHandler::dispatch($processHandlerName, $process);

    }


    public function dispatchAsync(CommandInterface $command): void
    {
        $this->dispatch($command, async: true);
    }

    /**
     * @throws Throwable
     */
    public function dispatchIgnoreException(CommandInterface $command, string $exceptionClass): void
    {
        try {
            $this->dispatch($command);
        } catch (Throwable $e) {
            if ($e instanceof $exceptionClass) {
                return;
            }
            throw $e;
        }
    }

    /**
     * @param  array<string>  $exceptionClasses
     *
     * @throws Throwable
     */
    public function dispatchIgnoreExceptions(CommandInterface $command, array $exceptionClasses): void
    {
        try {
            $this->dispatch($command);
        } catch (Throwable $e) {
            foreach ($exceptionClasses as $exceptionClass) {
                if ($e instanceof $exceptionClass) {
                    return;
                }
            }
            throw $e;
        }
    }
}
