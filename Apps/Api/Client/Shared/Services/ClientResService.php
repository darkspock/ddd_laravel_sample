<?php

declare(strict_types=1);

namespace Apps\Api\Client\Shared\Services;

use Apps\Api\Client\Shared\ClientRes;
use Src\Client\Application\Queries\GetById\GetClientByIdQuery;
use Src\Client\Domain\ValueObjects\ClientId;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryBusInterface;

final readonly class ClientResService
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    public function getClientResource(ClientId $clientId): ClientRes
    {
        $dto = $this->queryBus->query(new GetClientByIdQuery($clientId));

        return ClientRes::fromDto($dto);
    }
}
