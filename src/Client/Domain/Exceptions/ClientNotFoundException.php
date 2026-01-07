<?php

declare(strict_types=1);

namespace Src\Client\Domain\Exceptions;

use Src\Client\Domain\ValueObjects\ClientId;
use Src\Shared\Framework\Domain\Exceptions\VisibleExceptionInterface;

final class ClientNotFoundException extends \DomainException implements VisibleExceptionInterface
{
    public static function withId(ClientId $id): self
    {
        return new self(
            message: sprintf('Client with id "%s" not found', $id->getValue()),
            code: 404,
        );
    }
}
