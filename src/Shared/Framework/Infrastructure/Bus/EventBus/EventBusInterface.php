<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Bus\EventBus;

use Src\Shared\Framework\Domain\Events\DomainEvent;

/**
 * @method __invoke(DomainEvent $event)
 */
interface EventBusInterface
{
    /**
     * @param array<DomainEvent> $events
     * @return void
     */
    public function publishEvents(array $events): void;
}
