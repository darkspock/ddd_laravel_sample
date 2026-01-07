<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Bus\EventBus;

use Src\Shared\Framework\Domain\Events\DomainEvent;

/**
 * Simple event bus that dispatches events via Laravel's event system.
 */
final class SimpleEventBus implements EventBusInterface
{
    /**
     * @param  DomainEvent[]  $events
     */
    public function publishEvents(array $events): void
    {
        foreach ($events as $event) {
            event($event);
        }
    }
}
