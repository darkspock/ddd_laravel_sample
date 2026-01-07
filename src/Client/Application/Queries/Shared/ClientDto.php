<?php

declare(strict_types=1);

namespace Src\Client\Application\Queries\Shared;

use Src\Client\Domain\Entities\Client;

final readonly class ClientDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Client $client): self
    {
        return new self(
            id: $client->id->getValue(),
            name: $client->name,
            email: $client->email,
            phone: $client->phone,
            createdAt: $client->createdAt->format('Y-m-d H:i:s'),
        );
    }
}
