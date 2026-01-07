<?php

declare(strict_types=1);

namespace Src\Client\Infrastructure\Persistence;

use DateTimeImmutable;
use Src\Client\Domain\Entities\Client;
use Src\Client\Domain\ValueObjects\ClientId;

final class ClientMapper
{
    public static function toDomain(ClientModel $model): Client
    {
        $client = Client::reconstitute(
            id: ClientId::fromString($model->id),
            name: $model->name,
            email: $model->email,
            phone: $model->phone,
            createdAt: DateTimeImmutable::createFromInterface($model->created_at),
        );

        $client->setIsNew(false);

        return $client;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(Client $client): array
    {
        return [
            'id' => $client->id->getValue(),
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
        ];
    }
}
