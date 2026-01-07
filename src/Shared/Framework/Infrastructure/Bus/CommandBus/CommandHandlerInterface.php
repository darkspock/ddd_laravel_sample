<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Bus\CommandBus;

use Src\Shared\Framework\Application\Commands\CommandInterface;

/**
 * Interface CommandHandler
 *
 * @method __invoke(CommandInterface $command)
 */
interface CommandHandlerInterface
{
}
