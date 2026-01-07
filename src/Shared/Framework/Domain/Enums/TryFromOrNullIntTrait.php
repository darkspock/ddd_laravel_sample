<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\Enums;

trait TryFromOrNullIntTrait
{
    public static function tryFromOrNull(?int $value): ?static
    {
        if ($value === null) {
            return null;
        }

        return static::tryFrom($value);
    }
}
