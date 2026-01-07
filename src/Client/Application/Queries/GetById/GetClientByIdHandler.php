<?php

declare(strict_types=1);

namespace Src\Client\Application\Queries\GetById;

use Src\Client\Application\Queries\Shared\ClientDto;
use Src\Client\Domain\Repositories\ClientRepositoryInterface;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryHandlerInterface;

final readonly class GetClientByIdHandler implements QueryHandlerInterface
{
    public function __construct(
        private ClientRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetClientByIdQuery $query): ClientDto
    {
        $client = $this->repository->getById($query->clientId);

        return ClientDto::fromEntity($client);
    }
}
