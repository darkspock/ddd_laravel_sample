<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Bus\CommandBus;

use Src\Shared\Framework\Application\Commands\CommandInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QueueHandler implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var int|array<int> */
    public int|array $backoff = [60, 120];

    public function __construct(
        public string $handler,
        public CommandInterface $command,
    ) {
    }

    public function handle(): void
    {
        /** @var CommandHandlerInterface $handler */
        $handler = app($this->handler);
        $handler->__invoke($this->command);
    }
}
