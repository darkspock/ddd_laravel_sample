<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\Exceptions;

use Src\Shared\Framework\Helpers\MixedHelper;
use DomainException;

class InvalidIdentifierException extends DomainException
{
    /**
     * @param  mixed  $value
     */
    public function __construct(string $class, $value)
    {
        parent::__construct($class . ' ' . MixedHelper::getStringOrNull($value));
    }
}
