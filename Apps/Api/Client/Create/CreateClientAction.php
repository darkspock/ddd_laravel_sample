<?php

declare(strict_types=1);

namespace Apps\Api\Client\Create;

use Apps\Api\Client\Shared\ClientCreatedRes;
use Src\Client\Application\Commands\Create\CreateClientCommand;
use Src\Shared\Framework\Infrastructure\Bus\CommandBus\CommandBusInterface;

final readonly class CreateClientAction
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(CreateClientDto $dto): ClientCreatedRes
    {
        $this->commandBus->dispatch(new CreateClientCommand(
            id: $dto->id,
            name: $dto->name,
            email: $dto->email,
            phone: $dto->phone,
        ));

        return new ClientCreatedRes(id: $dto->id->getValue());
    }
}
