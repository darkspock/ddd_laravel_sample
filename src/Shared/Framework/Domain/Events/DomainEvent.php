<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Base class for domain events.
 * Events capture something meaningful that happened in the domain.
 */
abstract class DomainEvent
{
    use Dispatchable;
    use InteractsWithSockets;

    public readonly int $occurredOn;

    public function __construct()
    {
        $this->occurredOn = time();
    }

    /**
     * Returns the event name for identification.
     */
    abstract public function getName(): string;
}
