<?php

declare(strict_types=1);

namespace Apps\Api\Client\Shared;

use Apps\Shared\Http\BaseRes;
use Src\Client\Application\Queries\Shared\ClientDto;

final readonly class ClientRes extends BaseRes
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public string $createdAt,
    ) {
    }

    public static function fromDto(ClientDto $dto): self
    {
        return new self(
            id: $dto->id,
            name: $dto->name,
            email: $dto->email,
            phone: $dto->phone,
            createdAt: $dto->createdAt,
        );
    }
}
