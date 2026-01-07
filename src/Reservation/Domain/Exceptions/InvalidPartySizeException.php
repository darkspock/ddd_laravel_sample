<?php

declare(strict_types=1);

namespace Src\Reservation\Domain\Exceptions;

use Src\Shared\Framework\Domain\Exceptions\VisibleExceptionInterface;
use RuntimeException;

final class InvalidPartySizeException extends RuntimeException implements VisibleExceptionInterface
{
    public static function tooSmall(int $value, int $min): self
    {
        return new self("Party size {$value} is too small. Minimum is {$min}.");
    }

    public static function tooLarge(int $value, int $max): self
    {
        return new self("Party size {$value} is too large. Maximum is {$max}.");
    }
}
