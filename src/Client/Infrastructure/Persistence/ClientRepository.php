<?php

declare(strict_types=1);

namespace Src\Client\Infrastructure\Persistence;

use Src\Client\Domain\Entities\Client;
use Src\Client\Domain\Exceptions\ClientNotFoundException;
use Src\Client\Domain\Repositories\ClientRepositoryInterface;
use Src\Client\Domain\ValueObjects\ClientId;

final class ClientRepository implements ClientRepositoryInterface
{
    public function store(Client $client): void
    {
        ClientModel::updateOrCreate(
            ['id' => $client->id->getValue()],
            ClientMapper::toArray($client)
        );
    }

    public function findById(ClientId $id): ?Client
    {
        $model = ClientModel::find($id->getValue());

        if ($model === null) {
            return null;
        }

        return ClientMapper::toDomain($model);
    }

    public function getById(ClientId $id): Client
    {
        $client = $this->findById($id);

        if ($client === null) {
            throw ClientNotFoundException::withId($id);
        }

        return $client;
    }
}
