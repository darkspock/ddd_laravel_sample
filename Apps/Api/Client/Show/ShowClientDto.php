<?php

declare(strict_types=1);

namespace Apps\Api\Client\Show;

use Src\Client\Domain\ValueObjects\ClientId;

final readonly class ShowClientDto
{
    public function __construct(
        public ClientId $clientId,
    ) {
    }
}
