<?php

declare(strict_types=1);

namespace Src\Client\Application\Queries\GetById;

use Src\Client\Application\Queries\Shared\ClientDto;
use Src\Client\Domain\ValueObjects\ClientId;
use Src\Shared\Framework\Infrastructure\Bus\QueryBus\QueryInterface;

/**
 * @implements QueryInterface<ClientDto>
 */
final readonly class GetClientByIdQuery implements QueryInterface
{
    public function __construct(
        public ClientId $clientId,
    ) {
    }
}
