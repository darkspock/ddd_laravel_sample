<?php

declare(strict_types=1);

namespace Apps\Api\Client\Create;

use Src\Client\Domain\ValueObjects\ClientId;

final readonly class CreateClientDto
{
    public function __construct(
        public ClientId $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
    ) {
    }
}
