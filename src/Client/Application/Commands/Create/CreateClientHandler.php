<?php

declare(strict_types=1);

namespace Src\Client\Application\Commands\Create;

use Src\Client\Domain\Entities\Client;
use Src\Client\Domain\Repositories\ClientRepositoryInterface;
use Src\Shared\Framework\Infrastructure\Bus\CommandBus\CommandHandlerInterface;
use Src\Shared\Framework\Infrastructure\Bus\EventBus\EventBusInterface;

final readonly class CreateClientHandler implements CommandHandlerInterface
{
    public function __construct(
        private ClientRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(CreateClientCommand $command): void
    {
        $client = Client::create(
            id: $command->id,
            name: $command->name,
            email: $command->email,
            phone: $command->phone,
        );

        $this->repository->store($client);

        $this->eventBus->publishEvents($client->releaseEvents());
    }
}
