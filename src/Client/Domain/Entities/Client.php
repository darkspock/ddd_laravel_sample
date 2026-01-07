<?php

declare(strict_types=1);

namespace Src\Client\Domain\Entities;

use DateTimeImmutable;
use Src\Client\Domain\Events\ClientCreatedEvent;
use Src\Client\Domain\ValueObjects\ClientId;
use Src\Shared\Framework\Domain\Entities\BaseEntity;

final class Client extends BaseEntity
{
    /**
     * Private constructor - use create() for new clients or reconstitute() for hydration.
     */
    private function __construct(
        public readonly ClientId $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * Reconstitute a Client from persistence (hydration).
     *
     * This method allows setting ANY state without triggering events.
     * Use this ONLY for hydrating from database, never for creating new clients.
     */
    public static function reconstitute(
        ClientId $id,
        string $name,
        ?string $email,
        ?string $phone,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            phone: $phone,
            createdAt: $createdAt,
        );
    }

    public static function create(
        ClientId $id,
        string $name,
        ?string $email,
        ?string $phone,
    ): self {
        $client = new self(
            id: $id,
            name: $name,
            email: $email,
            phone: $phone,
            createdAt: new DateTimeImmutable(),
        );

        $client->recordLast(ClientCreatedEvent::fromEntity($client));

        return $client;
    }
}
