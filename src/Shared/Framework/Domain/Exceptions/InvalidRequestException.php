<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\Exceptions;

use DomainException;

final class InvalidRequestException extends DomainException
{
    /** @var int */
    protected $code = 422;

    public static function fromMessage(string $message): self
    {
        return new self($message);
    }
}
