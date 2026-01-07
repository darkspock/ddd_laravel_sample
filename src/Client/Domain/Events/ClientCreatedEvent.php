<?php

declare(strict_types=1);

namespace Src\Client\Domain\Events;

use Src\Client\Domain\Entities\Client;
use Src\Shared\Framework\Domain\Events\DomainEvent;

final class ClientCreatedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
    ) {
        parent::__construct();
    }

    public static function fromEntity(Client $client): self
    {
        return new self(
            clientId: $client->id->getValue(),
            name: $client->name,
            email: $client->email,
            phone: $client->phone,
        );
    }

    public function getName(): string
    {
        return 'client.created';
    }
}
