<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Domain\ValueObjects;

use RuntimeException;

final class CountryCode extends StringIdentifier
{
    /**
     * @param  string  $value
     */
    public static function create($value): self
    {
        if (strlen((string) $value) !== 2) {
            throw new RuntimeException('Invalid value for country ' . $value);
        }

        return parent::create(strtoupper((string) $value));
    }
}
